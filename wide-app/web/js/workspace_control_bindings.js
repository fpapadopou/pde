/*
 * The user interface utilities handle the HTML elements as the workspaces and their contents.
 * The WorkspaceManager maintains information about any changes that have been applied to
 * the workspaces and their files. The glue code below connects the two code sections.
 */
$('#file-creation-modal-btn').click(function () {
    workspace = WorkspaceManager.getActiveWorkspaceName();
    filename = $('#file-creation-modal-input').val();
    doAjaxRequest(
        createFileUrl,
        'POST',
        function (response) {
            WorkspaceManager.addFileToWorkspace({filename : filename, content : response.content});
            WorkspaceManager.setSelectedFile(filename);
            createNavFileList(WorkspaceManager.getActiveWorkspaceFiles());
            activateSelectedFile();
            $('#file-creation-modal').modal('hide');
        },
        {workspace : workspace, filename : filename}
    );
});

$('#file-rename-modal-btn').click(function () {
    workspace = WorkspaceManager.getActiveWorkspaceName();
    currentName = WorkspaceManager.getSelectedFile().filename;
    newName = $('#file-rename-modal-input').val();
    doAjaxRequest(
        renameFileUrl,
        'PUT',
        function () {
            WorkspaceManager.renameFile(currentName, newName);
            WorkspaceManager.setSelectedFile(newName);
            createNavFileList(WorkspaceManager.getActiveWorkspaceFiles());
            activateSelectedFile();
            $('#file-rename-modal').modal('hide');
        },
        {workspace : workspace, current_name : currentName, new_name : newName}
    );
});

$('#file-delete-modal-btn').click(function () {
    workspace = WorkspaceManager.getActiveWorkspaceName();
    filename = WorkspaceManager.getSelectedFile().filename;
    doAjaxRequest(
        deleteFileUrl,
        'DELETE',
        function () {
            WorkspaceManager.removeFileFromWorkspace(filename);
            createNavFileList(WorkspaceManager.getActiveWorkspaceFiles());
            activateSelectedFile();
            $('#file-delete-modal').modal('hide');
        },
        {workspace : workspace, filename : filename}
    );
});

$('#create-wspace').click(function () {
    date = new Date();
    datePart = date.getHours() + '_' + date.getMinutes() + '_' + date.getSeconds();
    doAjaxRequest(
        createWorkspaceUrl,
        'POST',
        function () {
            refreshWorkspaces(function () {
                createWorkspaceList(WorkspaceManager.getWorkspaces());
            });
        },
        {workspace : 'workspace_' + datePart}
    );
});

$('#save-wpsace-btn').click(function () {
    doAjaxRequest(
        saveWorkspaceUrl,
        'PUT',
        function () {
            WorkspaceManager.resetUnsavedChangesIndicator();
            $('#save-success').css('display', 'inline-block');
            $('#save-wpsace-btn').removeClass('btn-default').addClass('btn-success');
            setTimeout(function () {
                $('#save-success').css('display', 'none');
                $('#save-wpsace-btn').removeClass('btn-success').addClass('btn-default');
            }, 1000);
        },
        {workspace : WorkspaceManager.getActiveWorkspaceName(), files : WorkspaceManager.getActiveWorkspaceFiles()}
    );
});

$('#reload-btn').click(function () {
    currentWorkspace = WorkspaceManager.getActiveWorkspaceName();
    refreshWorkspaces(function () {
        createWorkspaceList(WorkspaceManager.getWorkspaces());
        WorkspaceManager.setActiveWorkspace(currentWorkspace);
        activateSelectedFile();
    });
});

$('#download-btn').click(function (event) {
    if (WorkspaceManager.getActiveWorkspaceFiles().length == 0) {
        event.preventDefault();
        infoModalMessage('There are no files in this workspace. Download canceled.');
        return;
    }

    if (WorkspaceManager.hasUnsavedChanges() === true) {
        event.preventDefault();
        infoModalMessage('There are unsaved changes - you need to save before you can download the workspace.');
        return;
    }
    location.href = downloadWorkspaceUrl + '?workspace=' + WorkspaceManager.getActiveWorkspaceName();
});

$('#wspace-delete-modal-btn').click(function () {
    doAjaxRequest(
        deleteWorkspaceUrl,
        'DELETE',
        function() {
            $('#wspace-delete-modal').modal('hide');
            refreshWorkspaces(function () {
                createWorkspaceList(WorkspaceManager.getWorkspaces());
                createNavFileList([]);
                setWorkspaceTitle();
                $('#wpsace-selection-modal').modal('show');
            });
        },
        {workspace : WorkspaceManager.getActiveWorkspaceName()}
    );
});

$('#wspace-rename-modal-btn').click(function () {
    if (WorkspaceManager.hasUnsavedChanges() === true) {
        infoModalMessage('There are unsaved changes - you need to save before you can rename the workspace.');
        return;
    }
    var currentName = WorkspaceManager.getActiveWorkspaceName();
    var newName = $('#wpsace-rename-modal-input').val();
    doAjaxRequest(
        renameWorkspaceUrl,
        'PUT',
        function() {
            refreshWorkspaces(function () {
                createWorkspaceList(WorkspaceManager.getWorkspaces());
                WorkspaceManager.setActiveWorkspace(newName);
                setWorkspaceTitle(newName);
            });
            $('#wspace-rename-modal').modal('hide');
        },
        {current_name : currentName, new_name : newName}
    );
});
