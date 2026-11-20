import { test, expect } from "@playwright/test";

test("testimonial carousel supports arrows, keyboard, resize, and all five cards", async ({
  page,
}) => {
  await page.emulateMedia({ reducedMotion: "reduce" });
  await page.goto("/#testimonials");
  const track = page.locator("#testimonials-track");
  const previous = page.getByRole("button", { name: "Previous testimonial" });
  const next = page.getByRole("button", { name: "Next testimonial" });
  const status = page.locator(".testimonials-status");
  await expect(track.locator("figure")).toHaveCount(5);
  for (const [width, visible] of [
    [390, 1],
    [768, 2],
    [1440, 3],
  ]) {
    await page.setViewportSize({ width, height: 960 });
    await track.focus();
    await page.keyboard.press("Home");
    await expect(status).toHaveText(`Testimonials 1 to ${visible} of 5`);
    await expect(previous).toBeDisabled();
    await next.click();
    await expect(status).toHaveText(`Testimonials 2 to ${visible + 1} of 5`);
    await previous.click();
    await expect(status).toHaveText(`Testimonials 1 to ${visible} of 5`);
    await track.focus();
    await page.keyboard.press("End");
    await expect(status).toHaveText(`Testimonials ${6 - visible} to 5 of 5`);
    await expect(next).toBeDisabled();
    await page.keyboard.press("ArrowLeft");
    await expect(status).toHaveText(`Testimonials ${5 - visible} to 4 of 5`);
    await page.keyboard.press("ArrowRight");
    await expect(next).toBeDisabled();
    expect(
      await page.evaluate(
        () => document.documentElement.scrollWidth <= innerWidth,
      ),
    ).toBe(true);
  }
});

test("testimonial cards remain scrollable without JavaScript", async ({
  browser,
}) => {
  const context = await browser.newContext({
    javaScriptEnabled: false,
    viewport: { width: 390, height: 844 },
  });
  const page = await context.newPage();
  await page.goto("http://localhost:8088/#testimonials");
  const track = page.locator("#testimonials-track");
  await expect(track.locator("figure")).toHaveCount(5);
  await expect(page.locator(".testimonials-controls")).toBeHidden();
  await track.hover();
  await page.mouse.wheel(3000, 0);
  await expect
    .poll(() =>
      track.evaluate((el) =>
        Math.abs(el.scrollLeft - (el.scrollWidth - el.clientWidth)),
      ),
    )
    .toBeLessThan(2);
  await expect(track.locator("figure").last()).toBeInViewport();
  await context.close();
});

test("desktop renders complete imagery, two-line hero, and a single-line navigation", async ({
  page,
}) => {
  const failures = [];
  page.on("pageerror", (error) => failures.push(error.message));
  page.on("response", (response) => {
    if (response.status() >= 400)
      failures.push(`${response.status()} ${response.url()}`);
  });
  await page.setViewportSize({ width: 1440, height: 960 });
  await page.goto("/");
  await page.evaluate(() => document.fonts.ready);
  await expect(page).toHaveTitle(/Lion Home Services/);
  await expect(page.locator("h1")).toHaveText("Your home.In good hands.");
  const layout = await page.evaluate(() => {
    const heading = document.querySelector("h1");
    const cta = document.querySelector(".hero .button").getBoundingClientRect();
    const nav = [...document.querySelectorAll(".navigation a")].map(
      (el) => el.getBoundingClientRect().top,
    );
    return {
      overflow: document.documentElement.scrollWidth > innerWidth,
      lines:
        heading.clientHeight / parseFloat(getComputedStyle(heading).lineHeight),
      ctaBottom: cta.bottom,
      nav,
    };
  });
  expect(layout.overflow).toBe(false);
  expect(layout.lines).toBeCloseTo(2, 0);
  expect(layout.ctaBottom).toBeLessThan(960);
  expect(new Set(layout.nav).size).toBe(1);
  for (const image of await page.locator("img").all()) {
    await image.scrollIntoViewIfNeeded();
    await expect(image).toHaveJSProperty("complete", true);
    expect(await image.evaluate((el) => el.naturalWidth)).toBeGreaterThan(0);
  }
  expect(failures).toEqual([]);
});

test("mobile navigation, service selection, FAQs, theme persistence, and no horizontal overflow", async ({
  page,
}) => {
  await page.setViewportSize({ width: 390, height: 844 });
  await page.goto("/");
  const menu = page.locator(".menu-toggle");
  await menu.click();
  await expect(menu).toHaveAttribute("aria-expanded", "true");
  await page
    .getByRole("navigation")
    .getByRole("link", { name: "Our services" })
    .click();
  await expect(menu).toHaveAttribute("aria-expanded", "false");
  await page
    .locator("details")
    .filter({ has: page.locator("summary", { hasText: "Furniture assembly" }) })
    .locator("summary")
    .click();
  await page.locator('[data-service="assembly"]').click();
  await expect(page.getByRole("dialog", { name: "Request a quote" })).toBeVisible();
  await expect(page.locator("#service")).toHaveValue("assembly");
  await page.getByRole("button", { name: "Close quote form" }).click();
  await page.getByRole("button", { name: "Switch to dark mode" }).click();
  await expect(page.locator("html")).toHaveAttribute("data-theme", "dark");
  await page.reload();
  await expect(page.locator("html")).toHaveAttribute("data-theme", "dark");
  await page.locator(".faq-item").first().locator("summary").click();
  await expect(page.locator(".faq-item").first()).toHaveAttribute("open", "");
  for (const width of [320, 375, 390, 768, 1024, 1440]) {
    await page.setViewportSize({ width, height: 900 });
    expect(
      await page.evaluate(
        () => document.documentElement.scrollWidth <= innerWidth,
      ),
      `overflow at ${width}`,
    ).toBe(true);
  }
});

test("a project is saved with a reference and duplicate submissions are idempotent", async ({
  page,
}) => {
  await page.goto("/");
  await page.locator("#name").fill("Website QA");
  await page.locator("#email").fill("qa@example.com");
  await page.locator("#zip").fill("10001");
  await page.getByRole("radio", { name: "Handyman" }).check();
  await page.locator("#service").selectOption("repairs");
  await page
    .locator("#message")
    .fill("Automated verification: adjust an interior door hinge.");
  await page.locator('[name="consent"]').check();
  const csrf = await page.locator('[name="csrf"]').inputValue();
  await page.locator(".form-submit").click();
  await expect(page.locator("#form-success")).toBeVisible();
  const reference = (await page.locator(".reference").textContent()).replace(
    "Reference: ",
    "",
  );
  expect(reference).toMatch(/^LHS-[A-F0-9]{8}$/);
  const duplicate = await page.request.post("/request.php", {
    headers: { Accept: "application/json" },
    form: {
      csrf,
      name: "Website QA",
      email: "qa@example.com",
      phone: "",
      zip: "10001",
      service: "repairs",
      message: "Automated verification: adjust an interior door hinge.",
      consent: "1",
      website: "",
    },
  });
  expect(duplicate.status()).toBe(200);
  expect((await duplicate.json()).reference).toBe(reference);
  await page.getByRole("link", { name: "Send another request" }).click();
  await expect(page.locator("#quote-form")).toBeVisible();
  await expect(page.locator("#name")).toBeEmpty();
});

test("server rejects missing CSRF, invalid fields, honeypot, and non-POST requests", async ({
  page,
}) => {
  await page.goto("/");
  const csrf = await page.locator('[name="csrf"]').inputValue();
  const rejected = await page.request.post("/request.php", {
    headers: { Accept: "application/json" },
    form: {},
  });
  expect(rejected.status()).toBe(403);
  const invalid = await page.request.post("/request.php", {
    headers: { Accept: "application/json" },
    form: { csrf },
  });
  expect(invalid.status()).toBe(422);
  expect(Object.keys((await invalid.json()).errors)).toEqual(
    expect.arrayContaining([
      "name",
      "email",
      "zip",
      "work_type",
      "consent",
    ]),
  );
  const bot = await page.request.post("/request.php", {
    headers: { Accept: "application/json" },
    form: { csrf, website: "bot" },
  });
  expect(bot.status()).toBe(422);
  expect((await page.request.get("/request.php")).status()).toBe(405);
});

test("network failure retains form data and allows retry", async ({ page }) => {
  await page.goto("/");
  await page.locator("#name").fill("Website QA");
  await page.locator("#email").fill("qa@example.com");
  await page.locator("#zip").fill("10001");
  await page.getByRole("radio", { name: "Handyman" }).check();
  await page.locator("#service").selectOption("assembly");
  await page
    .locator("#message")
    .fill("Please assemble a desk and a shelving unit.");
  await page.locator('[name="consent"]').check();
  await page.route("**/request.php", (route) => route.abort());
  await page.locator(".form-submit").click();
  await expect(page.locator("#form-error")).toContainText(
    "Check your connection",
  );
  await expect(page.locator("#name")).toHaveValue("Website QA");
  await expect(page.locator(".form-submit")).toBeEnabled();
});

test("without JavaScript form submits and returns server feedback", async ({
  browser,
}) => {
  const context = await browser.newContext({ javaScriptEnabled: false });
  const page = await context.newPage();
  await page.goto("http://localhost:8088/");
  await page.getByRole("radio", { name: "Handyman" }).check();
  await page.locator("#name").fill("Website QA no JS");
  await page.locator("#email").fill("qa@example.com");
  await page.locator("#zip").fill("10001");
  await page.locator("#service").selectOption("painting");
  await page
    .locator("#message")
    .fill("Automated verification without JavaScript: patch a wall.");
  await page.locator('[name="consent"]').check();
  await page.locator(".form-submit").click();
  await expect(page).toHaveURL("http://localhost:8088/#quote");
  await expect(page.locator("#form-success")).toBeVisible();
  await context.close();
});

const quotePng = Buffer.from(
  "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aC1kAAAAASUVORK5CYII=",
  "base64",
);
const quoteFile = (name, buffer = quotePng) => ({ name, mimeType: "image/png", buffer });

test("quote dialog and footer share cleaning selections, notes, and files", async ({ page }) => {
  await page.setViewportSize({ width: 390, height: 844 });
  await page.goto("/");
  const opener = page.locator(".hero").getByRole("link", { name: "Request a quote" });
  await opener.click();
  const dialog = page.getByRole("dialog", { name: "Request a quote" });
  await expect(dialog).toBeVisible();
  await expect(dialog.getByRole("heading", { name: "Request a quote" })).toBeFocused();
  await expect(dialog.getByLabel("Bedrooms")).toBeHidden();
  await dialog.getByRole("radio", { name: "Cleaning" }).check();
  await expect(dialog.getByLabel("Handyman service")).toBeHidden();
  await dialog.getByLabel("Bedrooms").selectOption("0");
  await dialog.getByLabel("Bathrooms").selectOption("1.5");
  const extras = dialog.getByRole("group", { name: "Cleaning extras" });
  await expect(extras.locator("label")).toHaveText([
    "Laundry",
    "Fridge cleaning",
    "Oven cleaning",
    "Interior window cleaning",
    "Cleaning a home with pets",
    "Baseboard cleaning",
    "Interior cabinet cleaning",
    "Basement cleaning",
  ]);
  await expect(extras.getByRole("checkbox")).toHaveCount(8);
  await expect(extras.getByRole("checkbox", { checked: true })).toHaveCount(0);
  await expect(dialog.getByLabel("Cleaning frequency")).toHaveCount(0);
  await dialog.getByLabel("Fridge cleaning").check();
  await dialog.getByLabel("Cleaning a home with pets").check();
  await dialog.getByLabel("Basement cleaning").check();
  await dialog.getByLabel("Anything else").fill("Please use fragrance-free products.");
  await dialog.getByLabel("Photos or screenshots").setInputFiles([
    quoteFile("kitchen.png"),
    quoteFile("bathroom.png"),
  ]);
  await page.keyboard.press("Escape");
  await expect(dialog).toBeHidden();
  await expect(opener).toBeFocused();
  const footer = page.locator("#quote-form-home");
  await expect(footer.getByLabel("Bedrooms")).toHaveValue("0");
  await expect(footer.getByLabel("Bathrooms")).toHaveValue("1.5");
  await expect(footer.getByLabel("Fridge cleaning")).toBeChecked();
  await expect(footer.getByLabel("Cleaning a home with pets")).toBeChecked();
  await expect(footer.getByLabel("Basement cleaning")).toBeChecked();
  await expect(footer.getByLabel("Anything else")).toHaveValue("Please use fragrance-free products.");
  expect(await footer.getByLabel("Photos or screenshots").evaluate((input) => input.files.length)).toBe(2);

  await footer.getByLabel("Your name").fill("Website QA cleaning");
  await footer.getByLabel("Email address").fill("qa@example.com");
  await footer.getByLabel("ZIP code").fill("10001");
  await footer.locator('[name="consent"]').check();
  await page.route("**/request.php", (route) => route.abort(), { times: 1 });
  await footer.getByRole("button", { name: "Request a quote" }).click();
  await expect(footer.locator("#form-error")).toContainText("Check your connection");
  expect(await footer.getByLabel("Photos or screenshots").evaluate((input) => input.files.length)).toBe(2);
  await footer.getByRole("button", { name: "Request a quote" }).click();
  await expect(footer.locator("#form-success")).toBeVisible();
});

test("cleaning validates both counts, allowed extras, image contents, count, and combined size", async ({ page }) => {
  await page.goto("/");
  const csrf = await page.locator('[name="csrf"]').inputValue();
  const fields = new FormData();
  for (const [name, value] of Object.entries({
    csrf, name: "Website QA validation", email: "qa@example.com", zip: "10001",
    work_type: "cleaning", bedrooms: "2", bathrooms: "1", consent: "1",
  })) fields.append(name, value);
  const post = () => page.request.post("/request.php", {
    headers: { Accept: "application/json" },
    multipart: fields,
  });
  fields.set("bedrooms", "200");
  fields.set("bathrooms", "1.75");
  fields.set("addons[]", "unknown");
  fields.append("attachments[]", new File(["Not an image"], "fake.png", { type: "image/png" }));
  let response = await post();
  expect(response.status()).toBe(422);
  expect(Object.keys((await response.json()).errors)).toEqual(
    expect.arrayContaining(["bedrooms", "bathrooms", "addons", "attachments"]),
  );

  fields.set("bedrooms", "2");
  fields.set("bathrooms", "1");
  fields.delete("addons[]");
  fields.delete("attachments[]");
  for (let i = 0; i < 6; i++) {
    fields.append("attachments[]", new File([quotePng], `image-${i}.png`, { type: "image/png" }));
  }
  response = await post();
  expect(response.status()).toBe(422);
  expect((await response.json()).errors.attachments).toContain("up to 5");

  fields.delete("attachments[]");
  const biggerPng = Buffer.concat([quotePng, Buffer.alloc(5_000_000)]);
  for (const name of ["first.png", "second.png"]) {
    fields.append("attachments[]", new File([biggerPng], name, { type: "image/png" }));
  }
  response = await post();
  expect(response.status()).toBe(422);
  expect((await response.json()).errors.attachments).toContain("10 MB");
});

test("without JavaScript a cleaning request keeps its details after validation", async ({ browser }) => {
  const context = await browser.newContext({ javaScriptEnabled: false });
  const page = await context.newPage();
  await page.goto("http://localhost:8088/#quote");
  const form = page.locator("#quote-form");
  await form.getByRole("radio", { name: "Cleaning" }).check();
  await form.getByLabel("Bedrooms").selectOption("0");
  await form.getByLabel("Baseboard cleaning").check();
  await form.getByLabel("Your name").fill("Website QA cleaning no JS");
  await form.getByLabel("Email address").fill("qa@example.com");
  await form.getByLabel("ZIP code").fill("10001");
  await form.getByLabel("Photos or screenshots").setInputFiles(quoteFile("kitchen.png"));
  await form.locator('[name="consent"]').check();
  const submit = form.getByRole("button", { name: "Request a quote" });
  await submit.click();
  await expect(form.locator("#bathrooms-error")).toHaveText("Choose the number of bathrooms.");
  await expect(form.locator("#attachments-help")).toContainText("Please select your images again.");
  await expect(form.getByRole("radio", { name: "Cleaning" })).toBeChecked();
  await expect(form.getByLabel("Bedrooms")).toHaveValue("0");
  await expect(form.getByLabel("Baseboard cleaning")).toBeChecked();
  await expect(form.getByLabel("Your name")).toHaveValue("Website QA cleaning no JS");

  await form.getByLabel("Bathrooms").selectOption("0.5");
  await form.getByLabel("Photos or screenshots").setInputFiles(quoteFile("kitchen.png"));
  await submit.click();
  await expect(page.locator("#form-success")).toBeVisible();
  await context.close();
});

test("system dark mode and reduced motion work without a stored preference", async ({
  browser,
}) => {
  const context = await browser.newContext({
    colorScheme: "dark",
    reducedMotion: "reduce",
  });
  const page = await context.newPage();
  await page.goto("http://localhost:8088/");
  await expect(
    page.getByRole("button", { name: "Switch to light mode" }),
  ).toBeVisible();
  expect(
    await page
      .locator("body")
      .evaluate((el) => getComputedStyle(el).backgroundColor),
  ).toBe("rgb(27, 33, 29)");
  expect(
    await page
      .locator(".hero-copy")
      .evaluate((el) => getComputedStyle(el).animationName),
  ).toBe("none");
  expect(await page.locator(".is-pending").count()).toBe(0);
  await context.close();
});
