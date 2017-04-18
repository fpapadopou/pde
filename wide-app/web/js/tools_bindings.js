/**
 * Handling of Bison, Flex, Gcc and simulation operations.
 */
$(document).ready(function () {
    $('#bison-btn').click(function () {
        if (WorkspaceManager.containsFileWithExtension('y') !== true) {
            infoModalMessage('You need to create a \'.y\' file in order to generate a syntax analyzer with GNU Bison.');
            return;
        }
        execUtility('bison');
    });
    $('#flex-btn').click(function () {
        if (WorkspaceManager.containsFileWithExtension('l') !== true) {
            infoModalMessage('You need to create a \'.l\' file in order to generate a lexical analyzer with Flex.');
            return;
        }
        execUtility('flex');
    });
    $('#gcc-btn').click(function () {
        var message = 'Some files are missing.';
        message += 'You need to generate a syntax analyzer with Bison and a scanner with Flex before you can compile your parser.';
        if (WorkspaceManager.containsFileWithExtension('tab.c') !== true
            || WorkspaceManager.containsFileWithExtension('yy.c') !== true) {
            infoModalMessage(message);
            return;
        }
        execUtility('gcc');
    });
    $('#simulation-btn').click(function () {
        if(WorkspaceManager.containsFileWithExtension('out') !== true) {
            infoModalMessage('No executable (.out) file found.');
            return;
        }
        var selectedFile = WorkspaceManager.getSelectedFile()['filename'];
        if (WorkspaceManager.getFileExtension(selectedFile) !== 'txt') {
            infoModalMessage('You need to select a \'.txt\' file in order to test your parser.');
            return;
        }
        execUtility('simulation', selectedFile);
    });
});
