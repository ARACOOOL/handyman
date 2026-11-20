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
  await expect(page.locator("#service")).toHaveValue("assembly");
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
      "service",
      "message",
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
