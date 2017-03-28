/**
 * This file contains functions that handle the view of the editor elements
 */

// Creates the list of workspaces used in the workspace selection modal.
createWorkspaceList = function (workspaceData) {
    // First clear the html of the list
    listSelector = $('#wspace-selection');
    listSelector.html('');
    // Populate the workspace list with the names of the workspace and some metadata
    for (i = 0; i < workspaceData.length; i++) {
        var dateObject = new Date(workspaceData[i].modified * 1000);
        modifiedDate = dateObject.toDateString() + ' ' + dateObject.getHours() + ':' + dateObject.getMinutes();
        listSelector.append(
            $('<a>')
            .attr('class', 'list-group-item')
            .attr('data-name', workspaceData[i].name)
            .css('text-align', 'center')
            .html(
                '<strong>' + workspaceData[i].name + '</strong> <small>last modified ' + modifiedDate + '</small>'
            ).click(function () {
                name = $(this).data('name');
                selectWorkspace(name);
                setWorkspaceTitle(name);
            })
        );
    }
};

// Sets the title of the workspace in the respective HTML element.
setWorkspaceTitle = function (name) {
    name = name || 'Workspace name';
    $('#workspace-name').text(name);
};

// Sets the content of the Ace editor.
setEditorContent = function (text) {
    editor.getSession().setValue(text, -1); // either this or store cursor pos from editor.getCursorPosition() function in the WorkspaceManager
};

setEditorAvailability = function(extension) {
    if (typeof extension === "undefined" || extension == '') {
        editor.setReadOnly(true);
        return;
    }

    var editableExtensions = ['y', 'l', 'input'];
    var readOnlyIndication = $('#generated-file-note');
    if (editableExtensions.includes(extension) === true) {
        editor.setReadOnly(false);
        readOnlyIndication.hide();
        return;
    }
    editor.setReadOnly(true);
    readOnlyIndication.show();
};

// Sets the focus on the selected file editor tab.
activateSelectedFile = function () {
    // Un-select all files
    $("#file-tab-list > li").removeClass('active');

    selectedFile = WorkspaceManager.getSelectedFile();
    if (selectedFile === null) {
        setEditorContent('');
        return;
    }
    var filename = selectedFile['filename'];
    var fileContent = selectedFile['content'];
    setEditorContent(fileContent);
    setEditorAvailability(selectedFile['extension']);

    $("#file-tab-list > li > a[data-filename='" + filename + "']").parent().addClass('active');
};

// Callback for file list tabs click event.
navFileListTabClickFunction = function (filename) {
    WorkspaceManager.setSelectedFile(filename);
    selectedFile = WorkspaceManager.getSelectedFile();
    fileContent = selectedFile['content'];
    setEditorContent(fileContent);
    setEditorAvailability(selectedFile['extension']);
};

// Populates the file nav list.
createNavFileList = function (files) {
    tabListSelector = $('#file-tab-list');
    tabListSelector.html('');

    if (files.length == 0) {
        tabListSelector.append('<li role="presentation"><a>No files</a></li>');
        setEditorAvailability();
        return;
    }

    for (i = 0; i < files.length; i++) {
        tabListSelector.append(
            $('<li>')
            .attr('role', 'presentation')
            .append(
                $('<a>')
                .attr('data-filename', files[i].filename)
                .text(files[i].filename)
                .click(function () {
                    filename = $(this).data('filename');
                    navFileListTabClickFunction(filename);
                    $('#file-tab-list > li').removeClass('active');
                    $(this).parent().addClass('active');
                })
        )
        );
    }
};

// Performs all necessary UI actions when a workspace is selected.
selectWorkspace = function (workspaceName) {
    WorkspaceManager.setActiveWorkspace(workspaceName);
    files = WorkspaceManager.getActiveWorkspaceFiles();
    if (files.length != 0) {
        WorkspaceManager.setFileList(files);
        createNavFileList(files);
        activateSelectedFile();
    } else {
        createNavFileList([]);
        setEditorContent('');
    }

    // After the selection is done, just hide the modal window
    $('#wpsace-selection-modal').modal('hide');
};

// Toggles the output element.
toggleOutput = function () {
    if ($('#output-section').hasClass('inactive')) {
        showOutput();
        return;
    }
    hideOutput();
};

// Shows the output element.
showOutput = function () {
    var output = $('#output-section');
    var iconOn = $('#toggle-on-icon');
    var iconOff = $('#toggle-off-icon');
    output.removeClass('inactive').addClass('active');
    $('#editor').removeClass('full-size').addClass('half-size');
    editor.resize();
    iconOn.css('display', 'inline-block');
    iconOff.css('display', 'none');
};

// Hides the output element
hideOutput = function () {
    var output = $('#output-section');
    var iconOn = $('#toggle-on-icon');
    var iconOff = $('#toggle-off-icon');
    output.removeClass('active').addClass('inactive');
    $('#editor').removeClass('half-size').addClass('full-size');
    editor.resize();
    iconOff.css('display', 'inline-block');
    iconOn.css('display', 'none');
};


// Appends text to the output element.
appendTextToOutput = function (text) {
    if (typeof text === "undefined" || text == '') {
        return;
    }
    var tokens = text.split("\n");
    // First, append the new text to the existing output..
    var output = $('#output-section');
    for (i = 0; i < tokens.length; i++) {
        output.append(
            $('<p>')
                .text(tokens[i])
        );
    }

    // Then scroll to the end of the output.
    // Using the `zero` index in order to get the DOM element from the jQuery object.
    var internalHeight = output[0].scrollHeight;
    output.scrollTop(internalHeight);
};

// Starts the animation of the ajax-in-process indicator.
showWorkingIndication = function () {
    var indication = $('#working-indication');
    indication.css('display', 'block');
    var dots = $('#dots');
    dots.html('');

    var interval = setInterval(function () {
        if (indication.css('display') != 'block') {
            clearInterval(interval);
        }
        if (dots.text() == '...') {
            dots.html('');
            return;
        }
        dots.append('.');
    }, 300);
};

// Hides the ajax indicator.
hideWorkingIndication = function () {
    $('#working-indication').css('display', 'none');
};
