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
    doAjaxRequest(getWorkspacesUrl, 'GET', function (response) {
        WorkspaceManager.setWorkspaces(response.workspaces);
        callableFunction();
    });
}

/*
 * Handles the response of bison/flex/gcc/simulation requests
 * Upon success, all workspaces are reloaded and the current one is selected again
 * Then the output (if any) is printed in the output element
 */
execUtilityCallback = function(response) {
    if (response.success === true) {
        var activeFile = WorkspaceManager.getSelectedFile()['filename'];

        refreshWorkspaces(function () {
            createWorkspaceList(WorkspaceManager.getWorkspaces());
            selectWorkspace(WorkspaceManager.getActiveWorkspaceName());
            WorkspaceManager.setSelectedFile(activeFile);
            activateSelectedFile();
        });
        var message = 'Operation completed.';
        if (typeof response.output !== "undefined" && response.output != '') {
            message = response.output;
        }
        appendTextToOutput(message);
        showOutput();
        return;
    }
    appendTextToOutput(response.error);
    showOutput();
};

// Executes one of the available tools (flex, bison, gcc or simulation of the .out file)
function execUtility(utilitySelection, input) {
    input = input || '';
    appendTextToOutput('>> ' + utilitySelection.toUpperCase() + ' output: ');
    doAjaxRequestWithOutput(
        utilitiesUrl,
        'POST',
        execUtilityCallback,
        {
            workspace : WorkspaceManager.getActiveWorkspaceName(),
            files : WorkspaceManager.getActiveWorkspaceFiles(),
            utility : utilitySelection,
            input : input
        });
}

// Triggers an alert window when a user tries to close the current tab without having saved all changes
function onBeforeUnloadFunction() {
    if (WorkspaceManager.hasUnsavedChanges() == true) {
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
