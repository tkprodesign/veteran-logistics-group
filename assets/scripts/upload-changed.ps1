param(
  [string]$FtpHost = "server48.shared.spaceship.host",
  [string]$FtpUser = "admin@veteranlogisticsgroup.us",
  [string]$FtpPass = "Wateva06@",
  # IMPORTANT: set this to your REAL web root on the server (see step 2 below)
  [string]$RemotePath = "/htdocs"
)

# Go to project root (scripts/ is inside project/scripts)
$projectRoot = Resolve-Path (Join-Path $PSScriptRoot "..")
Set-Location $projectRoot

if (-not (Get-Command git -ErrorAction SilentlyContinue)) {
  Write-Error "git is not installed or not in PATH."
  exit 1
}

if (-not (Get-Command curl.exe -ErrorAction SilentlyContinue)) {
  Write-Error "curl.exe is not available in PATH."
  exit 1
}

# Ensure we are in a git repo
git rev-parse --is-inside-work-tree 2>$null | Out-Null
if ($LASTEXITCODE -ne 0) {
  Write-Error "This folder is not a git repository."
  exit 1
}

# Collect changed files, ignore deletes, ignore .git and .vscode
$changed = git status --porcelain | ForEach-Object {
  $line = $_.TrimEnd()
  if ($line.Length -lt 4) { return }

  $status = $line.Substring(0, 2).Trim()
  if ($status -eq "D" -or $status -eq "DD") { return }

  $path = $line.Substring(3)
  if ($path -match " -> ") { $path = ($path -split " -> ")[-1] }

  if ($path -like ".git/*" -or $path -like ".vscode/*") { return }

  if ($path -and (Test-Path $path -PathType Leaf)) { $path }
} | Sort-Object -Unique

if (-not $changed -or $changed.Count -eq 0) {
  Write-Host "No changed files to upload."
  exit 0
}

# Normalize remote path
$rp = $RemotePath.TrimEnd('/')
if ($rp -notmatch '^/') { $rp = '/' + $rp }

Write-Host "Uploading $($changed.Count) changed file(s) to $FtpHost$rp ..."

$failed = 0
foreach ($file in $changed) {
  $urlPath = $file -replace "\\","/"
  $targetUrl = "ftp://$FtpHost$rp/$urlPath"

  Write-Host "Uploading: $file -> $targetUrl"
  & curl.exe --ftp-create-dirs -T "$file" "$targetUrl" --user "$FtpUser`:$FtpPass" --silent --show-error
  if ($LASTEXITCODE -ne 0) {
    Write-Host "Upload failed for $file (exit $LASTEXITCODE)."
    $failed++
  }
}

if ($failed -gt 0) {
  Write-Error "Done with $failed failed upload(s)."
  exit 1
}

Write-Host "Done. All files uploaded successfully."
