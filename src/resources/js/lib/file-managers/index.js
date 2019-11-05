import laravelFileManager from "./laravel-filemanager";
import mockFileUploader from "./mock-file-uploader";


export default function (fileManagerAlias, fileManagerOptions) {

const { data } = window.wp;

switch (fileManagerAlias) {
    case "laravel-filemanager":
    laravelFileManager(fileManagerOptions);
    break;
    default:
    mockFileUploader();
    data
        .dispatch("core/blocks")
        .removeBlockTypes(["core/cover", "core/gallery", "core/media-text"]);
    break;
}
}
