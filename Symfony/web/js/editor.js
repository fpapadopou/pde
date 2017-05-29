/**
 * WorkspaceManager initialization
 */
var WorkspaceManager = new WorkspaceManager();
/**
 * Ace editor initialization
 */
var editor = ace.edit("editor");
editor.setTheme("ace/theme/textmate");
editor.getSession().setMode("ace/mode/c_cpp");
editor.$blockScrolling = Infinity; // Suppresses a JS warning (suggested by documentation)
editor.session.setUseWrapMode(true);
/**
 * Updates the file code in WorkspaceManager whenever the editor is changed. The editor session 'change' event
 * will also trigger when the content of the editor is changed programmatically
 * More info here: https://github.com/ajaxorg/ace/issues/503
 */
editor.getSession().on('change', function() {
    if (editor.curOp && editor.curOp.command.name) {
        // User initiated changes are described by an operation command name like 'insertingstring', 'paste', etc
        // Other changes (like programmatically setting the editor content) should be ignored
        var editorContent = editor.getSession().getValue();
        WorkspaceManager.updateSelectedFileContent(editorContent);
        WorkspaceManager.updateCurrentFileInFileList();
        WorkspaceManager.updateWorkspaces();
    }

});

// Reloads all workspaces from the app's backend - can use a callable in order to enhance callback functionality
function refreshWorkspaces(callableFunction) {
    ajaxRequestWithDoneCallback(getWorkspacesUrl, 'GET', function (response) {
        if (response.success === true) {
            WorkspaceManager.setWorkspaces(response.workspaces);
            callableFunction();
            return;
        }
        // Make the reload section of the modal visible and prevent it from closing before showing the response error
        $('#info-modal-footer').show();
        var infoModal = $('#info-modal');
        infoModal.attr('data-backdrop', 'static');
        infoModal.attr('data-keyboard', 'false');
        infoModalMessage(response.error);
    });
}

/*
 * Handles the response of bison/flex/gcc/simulation requests
 * Upon success, all workspaces are reloaded and the current one is selected again
 * Then the output (if any) is printed in the output element
 */
runCommandCallback = function(response) {
    var executedCommand = 'none';
    if (typeof response.command !== "undefined" && response.command !== '') {
        executedCommand = response.command;
    }
    appendTextToOutput("Executed command: <strong>" + executedCommand + "</strong>");
    if (response.success === true) {
        var activeFile = WorkspaceManager.getSelectedFile()['filename'];

        // Files' contents are sent base-64 encoded from the app backend. They should be decoded before being added
        // to the WorkspaceManager.
        for (i = 0; i < response.files.length; i++) {
            // TODO: Might need a better way to tell which files should not be base-64 decoded.
            if (response.files[i]['extension'] === 'out') {
                continue;
            }
            decodedContent = atob(response.files[i]['content']);
            response.files[i]['content'] = decodedContent;
        }
        WorkspaceManager.setFileList(response.files);
        createNavFileList(WorkspaceManager.getActiveWorkspaceFiles());
        WorkspaceManager.setSelectedFile(activeFile);
        activateSelectedFile();

        var message = 'Operation completed.\n';
        if (typeof response.output !== "undefined" && response.output !== '') {
            message += response.output;
        }
        appendTextToOutput(message);
        showOutput();
        return;
    }
    appendTextToOutput('Operation failed.\n' + response.error);
    showOutput();
};

// Executes one of the available tools (flex, bison, gcc or simulation of the .out file)
function runCommand(utility, input) {
    input = input || '';
    var options = '';
    if (utility === 'bison' || utility === 'flex' || utility === 'gcc') {
        options += $('#' + utility + '-short-options').text() + ' ';
        options += $('#' + utility + '-long-options').text() + ' ';
        var argOption = $('#' + utility + '-arg-options').text();
        // Append '.out' extension to GCC output file, if necessary
        if (utility === 'gcc') {
            argOption += '.out';
        }
        options += argOption + ' ';
    }
    appendTextToOutput('>> ' + utility.toUpperCase() + ' output: ');
    ajaxRequestWithDoneCallback(
        runCommandUrl,
        'POST',
        runCommandCallback,
        {
            files : WorkspaceManager.getActiveWorkspaceFiles(),
            utility : utility,
            options: options,
            input : input
        });
}

// Triggers an alert window when a user tries to close the current tab without having saved all changes
function onBeforeUnloadFunction() {
    if (WorkspaceManager.hasUnsavedChanges() === true) {
        return 'There are unsaved changes in this workspace';
    }
}

// The statements below trigger an animation which indicates an ajax request is being processed
$(document).ajaxStart(function () {
    showWorkingIndication();
});
$(document).ajaxComplete(function () {
    hideWorkingIndication();
});
