$(document).ready(function () {
    // Selection modal button
    $('#trigger-modal-btn').click(function () {
        if (WorkspaceManager.hasUnsavedChanges() === true) {
            $('#unsaved-changes-modal').modal('show');
            return;
        }
        $('#wspace-selection-modal p').hide();
        refreshWorkspaces(function () {
            createWorkspaceList(WorkspaceManager.getWorkspaces());
            $('#wspace-selection-modal').modal('show');
        });
    });

    // User has unsaved changes but decides to select another workspace
    $('#unsaved-changes-leave-btn').click(function () {
        refreshWorkspaces(function () {
            $('.modal').modal('hide');
            $('#wspace-selection-modal').modal('show');
        });
    });

    // File/workspace operations modals
    $('#new-file-btn').click(function () {
        $('#file-creation-modal').modal('show');
    });
    $('#rename-file-btn').click(function () {
        $('#rename-file-placeholder').html(WorkspaceManager.getSelectedFile().filename);
        $('#file-rename-modal').modal('show');
    });
    $('#delete-file-btn').click(function () {
        $('#delete-file-placeholder').html(WorkspaceManager.getSelectedFile().filename);
        $('#file-delete-modal').modal('show');
    });
    $('#rename-wspace-btn').click(function () {
        $('#rename-wspace-placeholder').html(WorkspaceManager.getActiveWorkspaceName());
        $('#wspace-rename-modal').modal('show');
    });
    $('#delete-wspace-btn').click(function () {
        $('#delete-wspace-placeholder').html(WorkspaceManager.getActiveWorkspaceName());
        $('#wspace-delete-modal').modal('show');
    });

    // Button used for toggling the error output
    $('#toggle-output-btn').click(function () {
        toggleOutput();
    });
});
