import { mkdir, copyFile } from "node:fs/promises";
await mkdir("public/assets/fonts", { recursive: true });
await mkdir("public/assets/icons", { recursive: true });
await mkdir("public/assets/images", { recursive: true });
await copyFile(
  "node_modules/@fontsource-variable/manrope/files/manrope-latin-wght-normal.woff2",
  "public/assets/fonts/manrope-latin-wght-normal.woff2",
);
for (const [weight, font] of [
  ["regular", "Phosphor.woff2"],
  ["fill", "Phosphor-Fill.woff2"],
]) {
  await mkdir(`public/assets/icons/${weight}`, { recursive: true });
  for (const file of ["style.css", font]) {
    await copyFile(
      `node_modules/@phosphor-icons/web/src/${weight}/${file}`,
      `public/assets/icons/${weight}/${file}`,
    );
  }
}
await copyFile(
  "node_modules/@phosphor-icons/web/LICENSE",
  "public/assets/icons/LICENSE",
);
await copyFile(
  "node_modules/@fontsource-variable/manrope/LICENSE",
  "public/assets/fonts/LICENSE",
);
console.log("Self-hosted Manrope and Phosphor assets copied.");
