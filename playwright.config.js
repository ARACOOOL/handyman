import { defineConfig } from "@playwright/test";
export default defineConfig({
  testDir: "./tests",
  fullyParallel: false,
  workers: 1,
  use: {
    baseURL: "http://localhost:8088",
    browserName: "chromium",
    trace: "retain-on-failure",
  },
  reporter: "list",
});
