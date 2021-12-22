const browserSync = require("browser-sync").create();
const path = process.argv[2];

browserSync.init({
    watch: true,
    server: path
});