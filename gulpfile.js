const packageJSON = require("./package.json");
const { src, watch, dest, series } = require("gulp");
const wpPot = require("gulp-wp-pot");
const zip = require("gulp-zip");
const clean = require("gulp-clean");
const rename = require("gulp-rename");

const packageName = packageJSON.name;
const packageVersion = packageJSON.version;

const buildSrcFiles = [
    "./**/*",
    "!./**/_*/",
    "!./node_modules/**",
    "!./.csscomb.json",
    "!./.distignore",
    "!./.editorconfig",
    "!./.vscode/**",
    "!./.gitattributes",
    "!./.gitignore",
    "!./assets/dev/**",
    "!./package-lock.json",
    "!./package.json",
    "!./gulpfile.js",
    "!./yarn.lock",
    "!./README.md",
];

function makePot() {
    return src(["./*.php", "./**/*.php"])
        .pipe(
            wpPot({
                domain: "wootabs",
                package: "WooTabs",
            })
        )
        .pipe(dest("languages/wootabs.pot"));
}


function buildZip() {
    return src(buildSrcFiles, { base: "./" })
        .pipe(
            rename(function (file) {
                file.dirname = packageName + "/" + file.dirname;
            })
        )
        .pipe(zip(packageName +".zip"))
        .pipe(dest("../"));
}

function buildRelease() {
    return src(buildSrcFiles).pipe(dest("../wootabs-build"));
}

function deleteBuild() {
    return src(["../wootabs-build"], {
        read: false,
        allowEmpty: true,
    }).pipe(clean({ force: true }));
}

function deleteZip() {
    return src(["../wootabs.zip"], {
        read: false,
        allowEmpty: true,
    }).pipe(clean({ force: true }));
}

exports.build = series(deleteBuild, buildRelease);
exports.zip = series(deleteZip, buildZip);

exports.pot = makePot;