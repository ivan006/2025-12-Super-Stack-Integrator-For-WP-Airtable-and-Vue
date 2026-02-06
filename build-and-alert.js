const { exec } = require("child_process");
const notifier = require("node-notifier");

console.log("Starting build...");

exec("npm run build", (error, stdout, stderr) => {
  if (error) {
    console.error(`Error during build: ${error.message}`);
    notifier.notify({
      title: "Build Failed",
      message: error.message,
    });
    return;
  }
  if (stderr) {
    console.error(`Build stderr: ${stderr}`);
    notifier.notify({
      title: "Build Warning",
      message: stderr,
    });
    return;
  }
  console.log(`Build stdout: ${stdout}`);
  console.log("Build completed successfully!");
  notifier.notify({
    title: "Build Success",
    message: "Build completed successfully!",
  });
});
