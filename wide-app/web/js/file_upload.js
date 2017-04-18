/**
 * File upload related functionality.
 */
$(document).ready(function () {

    var input = $('#file-upload-input');
    var uploadButton = $('#file-upload-modal-btn');

    // Make sure FileReader is supported (most modern browsers).
    if (typeof window.FileReader !== 'function' || !input[0].files) {
        input.attr('disabled', 'disabled');
        uploadButton.attr('disabled', 'disabled');
        $('#file-upload-msg').show();
        return;
    }

    // Initialize upload parameters.
    var fileReader = new FileReader();
    var uploadFilename = '';
    var uploadFileContent = '';

    // Empty the input on every click, because the file is not updated if the user selects the same file.
    input.click(function () {
        uploadFilename = '';
        uploadFileContent = '';
        $(this).val('');
    });

    // Read the file when it's selected.
    input.change(function (event) {
        var file = event.target.files[0];
        uploadFilename = file.name;
        fileReader.onload = function(event) {
            // When the FileReader is done reading the file, the onload event is triggered
            uploadFileContent = event.target.result;
        };
        fileReader.readAsText(file);
    });

    // Handle the actual file upload.
    uploadButton.click(function () {
        var workspace = WorkspaceManager.getActiveWorkspaceName();

        if (uploadFileContent === '') {
            infoModalMessage('No file selected for upload or trying to upload an empty file.');
            return;
        }

        ajaxRequestWithSuccessHandler(
            uploadFileUrl,
            'POST',
            function (response) {
                WorkspaceManager.addFileToWorkspace({filename : response.filename, content : response.content});
                WorkspaceManager.setSelectedFile(response.filename);
                createNavFileList(WorkspaceManager.getActiveWorkspaceFiles());
                activateSelectedFile();
                $('#file-creation-modal').modal('hide');
            },
            {workspace : workspace, filename : uploadFilename, content : uploadFileContent}
        );
    });
});
