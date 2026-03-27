const vscode = require("vscode");

let gitTerminal;

function getGitTerminal() {
  if (!gitTerminal || gitTerminal.exitStatus !== undefined) {
    gitTerminal = vscode.window.createTerminal({
      name: "Git Bash Buttons",
      shellPath: "C:\\Program Files\\Git\\bin\\bash.exe"
    });
  }
  gitTerminal.show();
  return gitTerminal;
}

function runInBash(command) {
  const terminal = getGitTerminal();
  terminal.sendText(command, true);
}

function makeButton(text, command, tooltip) {
  const item = vscode.window.createStatusBarItem(vscode.StatusBarAlignment.Left, 100);
  item.text = text;
  item.command = command;
  item.tooltip = tooltip;
  item.show();
  return item;
}

function activate(context) {
  context.subscriptions.push(
    vscode.commands.registerCommand("gitButtons.pull", () => runInBash("git pull")),
    vscode.commands.registerCommand("gitButtons.status", () => runInBash("git status")),
    vscode.commands.registerCommand("gitButtons.diff", () => runInBash("git diff")),
    vscode.commands.registerCommand("gitButtons.fetch", () => runInBash("git fetch")),
    vscode.commands.registerCommand("gitButtons.log", () => runInBash('git log --oneline -n 10')),
    vscode.commands.registerCommand("gitButtons.pullStatus", () => runInBash("git pull && git status")),
    vscode.commands.registerCommand("gitButtons.fetchStatus", () => runInBash("git fetch && git status"))
  );

  const buttons = [
    makeButton("$(arrow-down) Pull", "gitButtons.pull", "Run git pull"),
    makeButton("$(source-control) Status", "gitButtons.status", "Run git status"),
    makeButton("$(compare-changes) Diff", "gitButtons.diff", "Run git diff"),
    makeButton("$(cloud-download) Fetch", "gitButtons.fetch", "Run git fetch"),
    makeButton("$(history) Log", "gitButtons.log", "Run git log --oneline -n 10"),
    makeButton("$(repo-sync) Pull+Status", "gitButtons.pullStatus", "Run git pull && git status"),
    makeButton("$(refresh) Fetch+Status", "gitButtons.fetchStatus", "Run git fetch && git status")
  ];

  buttons.forEach(btn => context.subscriptions.push(btn));
}

function deactivate() {}

module.exports = {
  activate,
  deactivate
};