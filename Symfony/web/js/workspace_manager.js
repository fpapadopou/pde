/**
 * WorkspaceManager class. Handles workspace- and file-related operations.
 */
WorkspaceManager = function () {
    this.workspaces = {};
    this.activeWorkspace = null;
    this.fileList = [];
    this.snapshot = [];
    this.selectedFile = null;

    // Sets the workspaces object contents.
    this.setWorkspaces = function (workspaces) {
        // Decode base-64 decoded files (except for .out files which are binaries)
        for (i = 0; i < workspaces.length; i++) {
            for (j = 0; j < workspaces[i]['files'].length; j++) {
                if (workspaces[i]['files'][j]['extension'] !== 'out') {
                    workspaces[i]['files'][j]['content'] = atob(workspaces[i]['files'][j]['content']);
                }
            }
        }
        this.workspaces = workspaces;
    };

    // Sets the currently active workspace by name.
    this.setActiveWorkspace = function (workspaceName) {
        var workspaces = this.workspaces;
        for (i = 0; i < workspaces.length; i++) {
            if (workspaceName === this.workspaces[i].name) {
                this.activeWorkspace = this.workspaces[i];
                this.activeWorkspace.isModified = false;
                this.setFileList(this.getActiveWorkspaceFiles());
                return true;
            }
        }
        console.log('Error - Cannot set active workspace (not found).');
        infoModalMessage('An error occurred. Reload the page to fix it.');
        return false;
    };

    // Returns the name of the active workspace.
    this.getActiveWorkspaceName = function () {
        return this.activeWorkspace.name;
    };

    // Returns the files of the active workspace.
    this.getActiveWorkspaceFiles = function () {
        if (this.activeWorkspace === null || !this.activeWorkspace.hasOwnProperty('files')) {
            console.log('Error - Current workspace has is either null or has no "files" property.');
            infoModalMessage('An error occurred. Reload current workspace to fix it.');
            return null;
        }

        return this.activeWorkspace.files;
    };

    // Returns all available workspaces.
    this.getWorkspaces = function() {
        return this.workspaces;
    };

    // Sets the current file list with the provided files.
    this.setFileList = function (files) {
        this.fileList = files;
        this.activeWorkspace.files = files;
        this.selectedFile = files[0];
        if (files.length === 0) {
            this.selectedFile = null;
        }
    };

    // Adds a file to the currently active workspace. Also updates the file list snapshot.
    this.addFileToWorkspace = function (file) {
        var fileObject = {filename : file.filename, extension : this.getFileExtension(file.filename), content : file.content};
        this.fileList.push(fileObject);
        this.snapshot.push(fileObject);
    };

    // Detects a file's extension.
    this.getFileExtension = function(filename) {
        var dotPosition = filename.indexOf('.');
        return filename.substr(dotPosition + 1);
    };

    // Removes a file from the currently active workspace. Also updates the file list snapshot.
    this.removeFileFromWorkspace = function (filename) {
        var fileList = this.fileList;
        this.fileList = removeFromFileList(filename, fileList);
        var snapshot = this.snapshot;
        this.snapshot = removeFromFileList(filename, snapshot);

    };

    // Removes the specified from the provided list and returns the updated list.
    // If the file does not exist in the provided list, returns the list as is.
    // List parameter must comply with the structure of the WorkspaceManager's file list.
    removeFromFileList = function (filename, list) {
        for (i = 0; i < list.length; i++) {
            if (list[i].filename === filename) {
                list.splice(i, 1);
                return list;
            }
        }
        return list;
    };

    // Renames a file in the current workspace.
    this.renameFile = function (currentName, newName) {
        var fileList = this.fileList;
        for (i = 0; i < fileList.length; i++) {
            if (fileList[i].filename === currentName) {
                fileList[i].filename = newName;
                break;
            }
        }
    };

    // Sets the currently selected file.
    this.setSelectedFile = function (filename) {
        var fileList = this.fileList;
        for (i = 0; i < fileList.length; i++) {
            if (filename === fileList[i].filename) {
                this.selectedFile = this.fileList[i];
                return true;
            }
        }
        infoModalMessage('An error occurred. Reload current workspace to fix it.');
        return false;
    };

    // Returns the selected file object.
    this.getSelectedFile = function () {
        return this.selectedFile;
    };

    // Updates the content of the selected file. Handles null selected file.
    this.updateSelectedFileContent = function (content) {
        if (this.selectedFile === null) {
            return;
        }
        // Set the unsaved changes flag
        this.activeWorkspace.isModified = true;
        this.selectedFile['content'] = content;
    };

    // Updates the active workspace file list when the selected file is modified.
    this.updateCurrentFileInFileList = function () {
        var fileList = this.fileList;
        var file = this.selectedFile;
        for (i = 0; i < fileList.length; i++) {
            if (file.filename === fileList[i].filename) {
                this.fileList[i] = this.selectedFile;
                this.activeWorkspace.files = this.fileList;
            }
        }
    };


    // Updates the workspaces array with the latest state of the active workspace.
    this.updateWorkspaces = function () {
        if (this.activeWorkspace === null) {
            return;
        }
        var name = this.getActiveWorkspaceName();
        var workspaces = this.workspaces;
        for (i = 0; i < workspaces.length; i++) {
            if (workspaces[i].name === name) {
                this.workspaces[i] = this.activeWorkspace;
                break;
            }
        }
    };

    // Returns whether the current workspace has been modified.
    this.hasUnsavedChanges = function () {
        if (typeof this.activeWorkspace === "undefined" || this.activeWorkspace === null) {
            return false;
        }
        // If the snapshot does not have the same number of files as the file list, there are
        // definitely unsaved changes, so the user will be prompted to save.
        var isSnapshotOutdated = this.fileList.length !== this.snapshot.length;
        return this.activeWorkspace.isModified || isSnapshotOutdated;
    };

    // Resets the unsaved changes flag.
    this.resetUnsavedChangesIndicator = function () {
        this.activeWorkspace.isModified = false;
    };

    // Can identify whether a file with the requested extension exists in the workspace.
    this.containsFileWithExtension = function (extension) {
        if (typeof extension === "undefined" || extension === '') {
            return false;
        }
        var fileList = this.fileList;
        for (i = 0; i < fileList.length; i++) {
            if (fileList[i].extension === extension) {
                return true;
            }
        }
        return false;
    };

    // Creates a snapshot of the current file list. The snapshot is updated on every file create/remove operation,
    // which are performed directly on the application backend.
    this.createSnapshot = function () {
        this.snapshot = this.fileList.slice();
    };

    // Determines whether a filename exists in the last snapshot of the
    this.isFileSaved = function (filename) {
        for (i = 0; i < this.snapshot.length; i++) {
            if (this.snapshot[i].filename === filename) {
                return true;
            }
        }
        return false;
    };

};
