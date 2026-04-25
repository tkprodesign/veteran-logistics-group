<?php include("../app.php"); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Users | Control Panel</title>
    <link rel="stylesheet" href="/assets/stylesheets/main.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="/assets/stylesheets/control-panel.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <link rel="shortcut icon" href="/assets/images/branding/mark-only.png?v=<?php echo time(); ?>" type="image/png">
</head>
<body>
    <?php include("../partials/header.php"); ?>

    <div class="header-2">
        <div class="container">
            <h2 class="greeting"><span class="material-symbols-outlined" aria-hidden="true">groups</span> Site Users</h2>
            <h1 class="cutomer-name">All Registered Users</h1>
        </div>
    </div>

    <div class="container content">
        <section class="cp-card">
            <div class="cp-card-head">
                <div>
                    <h2>User Directory</h2>
                    <p>Complete list of users in the system</p>
                </div>
                <a class="cp-btn cp-btn-secondary" href="/control-panel/page/">Back to Control Panel</a>
            </div>
            <div class="cp-table-wrap">
                <table class="cp-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Username</th>
                            <th>Country Code</th>
                            <th>Phone</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $usersSql = "SELECT id, name, email, username, country_code, phone_number, created_at FROM users ORDER BY id DESC";
                        $usersResult = $dbconn->query($usersSql);
                        if ($usersResult && $usersResult->num_rows > 0):
                            while ($u = $usersResult->fetch_assoc()):
                                $joinedTs = (int)$u['created_at'];
                                if ($joinedTs > 1000000000000) {
                                    $joinedTs = (int)($joinedTs / 1000);
                                }
                                $joinedDisplay = $joinedTs > 0 ? date("M d, Y H:i", $joinedTs) : "-";
                        ?>
                        <tr>
                            <td><?= (int)$u['id'] ?></td>
                            <td><?= htmlspecialchars((string)$u['name']) ?></td>
                            <td><?= htmlspecialchars((string)$u['email']) ?></td>
                            <td><?= htmlspecialchars((string)$u['username']) ?></td>
                            <td><?= htmlspecialchars((string)$u['country_code']) ?></td>
                            <td><?= htmlspecialchars((string)$u['phone_number']) ?></td>
                            <td><?= htmlspecialchars($joinedDisplay) ?></td>
                        </tr>
                        <?php
                            endwhile;
                        else:
                        ?>
                        <tr>
                            <td colspan="7">No users found.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <?php include("../../common-sections/footer.html"); ?>
    <script src="/assets/scripts/control-panel-tables.js?v=<?php echo time(); ?>"></script>
</body>
</html>
