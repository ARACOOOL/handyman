<?php
require dirname(__DIR__) . '/app/bootstrap.php';
$email = getenv('BUSINESS_EMAIL') ?: '';
$phone = getenv('BUSINESS_PHONE') ?: '';
$area = getenv('SERVICE_AREA') ?: '';
$thumbtackReviewsUrl = 'https://www.thumbtack.com/ia/waukee/furniture-assembly/lion-home-services/service/592235481551003652#ServicePageReviewsSection';
$socialProfiles = [
    ['Facebook', 'facebook-logo', getenv('FACEBOOK_URL') ?: '', 'facebook.com'],
    ['Instagram', 'instagram-logo', getenv('INSTAGRAM_URL') ?: '', 'instagram.com'],
];
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$old = $flash['old'] ?? [];
$errors = $flash['errors'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Lion Home Services helps with home repairs, furniture assembly, mounting, painting, carpentry, and maintenance. Tell us about your project.">
    <meta name="theme-color" content="#f6f7f4">
    <title>Lion Home Services | Your home. In good hands.</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="preload" href="/assets/fonts/manrope-latin-wght-normal.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/assets/images/handyman-outdoor.webp" as="image" fetchpriority="high">
    <link rel="stylesheet" href="/assets/icons/regular/style.css">
    <link rel="stylesheet" href="/assets/icons/fill/style.css">
    <link rel="stylesheet" href="/assets/site.css?v=<?= substr(hash_file('sha256', __DIR__ . '/assets/site.css'), 0, 12) ?>">
    <script src="/assets/site.js?v=<?= substr(hash_file('sha256', __DIR__ . '/assets/site.js'), 0, 12) ?>" defer></script>
</head>
<body>
<a class="skip-link" href="#main">Skip to content</a>
<header class="site-header">
    <div class="container nav-wrap">
        <a class="brand" href="/" aria-label="Lion Home Services home"><span class="brand-mark"><?= icon('house-line') ?></span><span class="brand-name">LION <span>HOME SERVICES</span></span></a>
        <nav id="navigation" class="navigation" aria-label="Main navigation">
            <a href="#services">Our services</a><a href="#approach">The Lion approach</a><a href="#questions">FAQs</a>
        </nav>
        <div class="nav-actions">
            <button class="icon-button theme-toggle" type="button" aria-label="Switch to dark mode"><?= icon('moon') ?></button>
            <a class="button button-dark nav-quote" href="#quote">Request a quote <?= icon('arrow-up-right') ?></a>
            <button class="icon-button menu-toggle" type="button" aria-label="Open navigation" aria-controls="navigation" aria-expanded="false"><?= icon('list') ?></button>
        </div>
    </div>
</header>
<main id="main">
    <section class="hero container" aria-labelledby="hero-title">
        <div class="hero-copy">
            <p class="eyebrow">A little help. A better home.</p>
            <h1 id="hero-title">Your home.<br>In good hands<span class="accent-period">.</span></h1>
            <p class="hero-description">From everyday fixes to thoughtful improvements, we help you love the place you call home.</p>
            <div class="hero-actions"><a class="button button-accent" href="#quote">Request a quote <?= icon('arrow-up-right') ?></a><a class="text-link" href="#services">Explore services <?= icon('arrow-down') ?></a></div>
        </div>
        <div class="hero-visual"><div class="photo-frame photo-frame--hero"><img class="photo-frame__media hero-photo" src="/assets/images/handyman-outdoor.webp" width="1200" height="1200" alt="Handyman in a black work shirt and tool belt, standing outside a sage-green house" fetchpriority="high"></div></div>
    </section>

    <section class="care-strip container" aria-label="Our commitments">
        <p>A little less on your list.<br><strong>A lot more peace of mind.</strong></p>
        <div><?= icon('chat-circle-text') ?><span>Clear communication</span></div>
        <div><?= icon('ruler') ?><span>Care in every detail</span></div>
        <div><?= icon('broom') ?><span>Respect for your space</span></div>
    </section>

    <section id="stats" class="stats-section container" aria-labelledby="stats-title">
        <div class="stats-shell reveal">
            <h2 id="stats-title">Good work, from the start.</h2>
            <?php // The first three business figures were supplied by the owner. ?>
            <dl class="stats-list">
                <div class="stat">
                    <dt>Happy Customers<span class="stat-note">Trusted by local homeowners</span></dt>
                    <dd>20<span class="stat-unit">+</span></dd>
                </div>
                <div class="stat">
                    <dt>Average Small Repair<span class="stat-note">Quick and professional service</span></dt>
                    <dd><span class="stat-unit">~</span>25 <span class="stat-unit">min</span></dd>
                </div>
                <div class="stat">
                    <dt>Owner-Operated<span class="stat-note">Personal attention on every job</span></dt>
                    <dd>100<span class="stat-unit">%</span></dd>
                </div>
                <div class="stat">
                    <dt>Thumbtack Rating<span class="stat-note">Based on 13 reviews</span></dt>
                    <dd aria-label="4.9 out of 5 stars">4.9 <?= icon('star', 'stat-rating-icon') ?></dd>
                </div>
            </dl>
        </div>
    </section>

    <section id="services" class="section services-section container" aria-labelledby="services-title">
        <div class="section-heading reveal"><h2 id="services-title">Big plans. Small fixes.<br>We’re here for both.</h2><p>One place for the jobs your home needs. Find your project below, or tell us what you have in mind.</p></div>
        <div class="services-layout">
            <div class="service-photo reveal"><div class="photo-frame"><img class="photo-frame__media" src="/assets/images/carpentry.webp" width="900" height="1200" loading="lazy" alt="Craftsperson carefully finishing a natural oak shelf"></div><div class="photo-caption"><span>Good work starts with<br>the little details.</span><?= icon('arrow-up-right') ?></div></div>
            <div class="service-list">
                <?php foreach ($services as $key => [$title, $description, $glyph, $items]): ?>
                <details class="service-item reveal" <?= $key === 'repairs' ? 'open' : '' ?>>
                    <summary><span class="service-icon"><?= icon($glyph) ?></span><span><?= escape($title) ?></span><span class="detail-toggle"><?= icon('plus') ?></span></summary>
                    <div class="service-content"><p><?= escape($description) ?></p><ul><?php foreach ($items as $item): ?><li><?= escape($item) ?></li><?php endforeach; ?></ul><a href="#quote" class="service-select text-link" data-service="<?= escape($key) ?>">Request a quote <?= icon('arrow-up-right') ?></a></div>
                </details>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section id="approach" class="section approach-section" aria-labelledby="approach-title">
        <div class="container approach-grid">
            <div class="approach-copy reveal"><p class="eyebrow">The Lion approach</p><h2 id="approach-title">More care.<br>Less hassle.</h2><p>Inviting someone into your home is personal. We believe the experience should feel as good as the finished work.</p><div class="approach-points"><div><?= icon('handshake') ?><h3>Agree before we begin.</h3><p>Talk through the job, the scope, and the cost before any work starts.</p></div><div><?= icon('house-heart') ?><h3>It’s your home. We treat it that way.</h3><p>Careful preparation, thoughtful work, and a tidy space when we’re done.</p></div></div></div>
            <div class="approach-image reveal"><div class="photo-frame"><img class="photo-frame__media" src="/assets/images/handyman-repair.webp" width="1600" height="900" loading="lazy" alt="Handyman repairing a wall outlet in a living room"></div></div>
        </div>
    </section>

    <section class="section process-section container" aria-labelledby="process-title">
        <h2 id="process-title" class="reveal">From to-do to done.</h2>
        <ol class="process-list">
            <li class="reveal"><span class="process-symbol"><?= icon('chat-teardrop-text') ?></span><h3>Tell us what you need.</h3><p>A single repair or a whole list. Share a few details to get the conversation started.</p></li>
            <li class="reveal"><span class="process-symbol"><?= icon('clipboard-text') ?></span><h3>Make a clear plan.</h3><p>We’ll discuss the details, confirm what’s involved, and find a time that works.</p></li>
            <li class="reveal"><span class="process-symbol"><?= icon('check') ?></span><h3>Enjoy your space.</h3><p>We take care of the work. You get back to feeling at home.</p></li>
        </ol>
    </section>

    <section id="testimonials" class="section testimonials-section container" aria-labelledby="testimonials-title" aria-describedby="testimonials-notice">
        <div class="testimonials-shell">
        <div class="testimonials-heading reveal">
            <h2 id="testimonials-title">Good work. Happy homes.</h2>
            <p id="testimonials-notice">Selected reviews from customers who hired us on Thumbtack, summarized below.</p>
            <a class="text-link" href="<?= escape($thumbtackReviewsUrl) ?>">Read all reviews on Thumbtack <?= icon('arrow-up-right') ?></a>
        </div>
        <div class="testimonials-carousel reveal" role="region" aria-roledescription="carousel" aria-label="Customer testimonials from Thumbtack">
            <div id="testimonials-track" class="testimonials-track" tabindex="0" aria-label="Testimonial cards. Use the left and right arrow keys to browse.">
                <?php
                $testimonials = [
                    ['Lev replied promptly, worked around Aspen’s schedule, and explained the problem and the repair.', 'Aspen j.', 'Handyman repair', 5],
                    ['Lisa appreciated the clear communication and fair, accurate quote for replacing a range hood and a kitchen light.', 'Lisa M.', 'Range hood and light replacement', 5],
                    ['Paula praised the quality and speed of Lev’s work assembling her dining room set.', 'Paula B.', 'Dining room furniture assembly', 5],
                    ['Kirsten appreciated the next-day availability, early arrival, quality work, and easy communication.', 'Kirsten G.', 'Furniture assembly', 5],
                    ['Lev arrived on time with his tools and assembled Karen’s outdoor furniture in under an hour.', 'Karen K.', 'Outdoor furniture assembly', 5],
                ]; foreach ($testimonials as $index => [$summary, $name, $project, $rating]): ?>
                <figure class="testimonial" role="group" aria-roledescription="slide" aria-label="<?= $index + 1 ?> of <?= count($testimonials) ?>">
                    <span class="testimonial-rating" role="img" aria-label="<?= $rating ?> out of 5 stars"><?php for ($star = 0; $star < $rating; $star++): ?><?= icon('star', 'ph-fill') ?><?php endfor; ?></span>
                    <p class="testimonial-summary"><?= escape($summary) ?></p>
                    <figcaption><strong><?= escape($name) ?></strong><span><?= escape($project) ?></span></figcaption>
                </figure>
                <?php endforeach; ?>
            </div>
            <div class="testimonials-controls" hidden>
                <p class="testimonials-status" role="status" aria-live="polite" aria-atomic="true"></p>
                <div class="testimonials-arrows">
                    <button class="carousel-button" type="button" data-direction="-1" aria-label="Previous testimonial" aria-controls="testimonials-track"><?= icon('arrow-left') ?></button>
                    <button class="carousel-button" type="button" data-direction="1" aria-label="Next testimonial" aria-controls="testimonials-track"><?= icon('arrow-right') ?></button>
                </div>
            </div>
        </div>
        </div>
    </section>

    <section id="questions" class="section faq-section container" aria-labelledby="faq-title">
        <div class="faq-heading reveal"><h2 id="faq-title">A few things<br>you might be wondering.</h2><p>Good questions make for a good start.</p></div>
        <div class="faq-list">
            <?php $faqs = [
                ['Can I bundle several small jobs?', 'Absolutely. Include your to-do list in the request, and we can discuss tackling several jobs during one visit.'],
                ['How do I get a quote?', 'Use the project form below. Describe the work and include your ZIP code. We’ll follow up to discuss the details before confirming the scope and cost.'],
                ['Do I need to supply materials?', 'It depends on the project. Tell us what you already have, and we’ll work out the materials needed before the visit.'],
                ['What if my project isn’t listed?', 'Tell us about it. We’ll let you know whether it’s a fit. Work that requires a licensed specialist should be handled by the appropriate professional.'],
                ['What areas do you serve?', $area !== '' ? 'We serve ' . $area . '. Include your ZIP code so we can confirm availability for your address.' : 'Include your ZIP code in the project request so we can confirm whether your home is within our service area.'],
            ]; foreach ($faqs as [$question, $answer]): ?>
            <details class="faq-item reveal"><summary><?= escape($question) ?><span class="detail-toggle"><?= icon('plus') ?></span></summary><p><?= escape($answer) ?></p></details>
            <?php endforeach; ?>
        </div>
    </section>

    <section id="quote" class="section quote-section" aria-labelledby="quote-title">
        <div class="container quote-grid">
            <div class="quote-copy reveal"><p class="eyebrow">Let’s get it off your list</p><h2 id="quote-title">What can we<br>take care of?</h2><p>Tell us a little about your project. We’ll work out the next steps together.</p><div class="quote-note"><?= icon('notepad') ?><p>Not sure exactly what you need?<br>Start with what you’d like to change.</p></div>
                <?php if ($email !== '' || $phone !== ''): ?><div class="contact-details"><?php if ($email !== ''): ?><a href="mailto:<?= escape($email) ?>"><?= escape($email) ?></a><?php endif; ?><?php if ($phone !== ''): ?><a href="tel:<?= escape(preg_replace('/[^+0-9]/', '', $phone)) ?>"><?= escape($phone) ?></a><?php endif; ?></div><?php endif; ?>
            </div>
            <div class="quote-form-wrap reveal">
                <div id="form-success" class="form-success" role="status" <?= isset($flash['success']) ? '' : 'hidden' ?>><?= icon('check-circle') ?><h3>One less thing to think about.</h3><p>Your project request has been saved. Thank you for telling us what your home needs.</p><p class="reference"><?= isset($flash['reference']) ? 'Reference: ' . escape($flash['reference']) : '' ?></p><a class="text-link" id="another-request" href="/#quote">Send another request <?= icon('arrow-up-right') ?></a></div>
                <form id="quote-form" method="post" action="/request.php" <?= isset($flash['success']) ? 'hidden' : '' ?>>
                    <input type="hidden" name="csrf" value="<?= escape($_SESSION['csrf']) ?>">
                    <div class="honeypot" aria-hidden="true"><label for="website">Leave this blank</label><input id="website" name="website" tabindex="-1" autocomplete="off"></div>
                    <p class="form-intro">Your project starts here.</p>
                    <div id="form-error" class="form-error" role="alert" <?= isset($errors['_form']) ? '' : 'hidden' ?>><?= escape($errors['_form'] ?? '') ?></div>
                    <div class="form-grid">
                    <?php foreach ([['name', 'Your name', 'text', 'name', 'Your full name'], ['email', 'Email address', 'email', 'email', 'you@example.com'], ['phone', 'Phone number (optional)', 'tel', 'tel', '(555) 000-0000'], ['zip', 'ZIP code', 'text', 'postal-code', 'Your ZIP code']] as [$field, $label, $type, $autocomplete, $placeholder]): ?>
                        <div class="field"><label for="<?= $field ?>"><?= $label ?></label><input id="<?= $field ?>" name="<?= $field ?>" type="<?= $type ?>" autocomplete="<?= $autocomplete ?>" placeholder="<?= $placeholder ?>" value="<?= escape($old[$field] ?? '') ?>" maxlength="<?= $field === 'email' ? '254' : '100' ?>" <?= $field !== 'phone' ? 'required' : '' ?> <?= $field === 'zip' ? 'inputmode="numeric" pattern="[0-9]{5}(-[0-9]{4})?"' : '' ?> aria-describedby="<?= $field ?>-error" <?= isset($errors[$field]) ? 'aria-invalid="true"' : '' ?>><span class="field-error" id="<?= $field ?>-error"><?= escape($errors[$field] ?? '') ?></span></div>
                    <?php endforeach; ?>
                    <div class="field field-wide"><label for="service">What can we help with?</label><select id="service" name="service" required aria-describedby="service-error"><option value="">Choose a service</option><?php foreach ($services as $key => $service): ?><option value="<?= $key ?>" <?= ($old['service'] ?? '') === $key ? 'selected' : '' ?>><?= escape($service[0]) ?></option><?php endforeach; ?><option value="other" <?= ($old['service'] ?? '') === 'other' ? 'selected' : '' ?>>A few things / something else</option></select><span class="field-error" id="service-error"><?= escape($errors['service'] ?? '') ?></span></div>
                    <div class="field field-wide"><label for="message">Tell us about your project</label><textarea id="message" name="message" rows="3" minlength="10" maxlength="3000" required placeholder="What needs fixing, building, or a little attention?" aria-describedby="message-error"><?= escape($old['message'] ?? '') ?></textarea><span class="field-error" id="message-error"><?= escape($errors['message'] ?? '') ?></span></div>
                    </div>
                    <label class="consent"><input type="checkbox" name="consent" value="1" required <?= ($old['consent'] ?? '') === '1' ? 'checked' : '' ?> aria-describedby="consent-error"><span>You may contact me about this request. My details will only be used to respond to my project.</span></label><span class="field-error" id="consent-error"><?= escape($errors['consent'] ?? '') ?></span>
                    <button class="button button-dark form-submit" type="submit"><span>Request a quote</span><?= icon('arrow-up-right') ?></button>
                    <p class="form-footnote">No commitment. Just a conversation about your home.</p>
                </form>
            </div>
        </div>
    </section>
</main>
<footer class="site-footer container">
    <a class="brand" href="/" aria-label="Lion Home Services home"><span class="brand-mark"><?= icon('house-line') ?></span><span class="brand-name">LION <span>HOME SERVICES</span></span></a>
    <p>A little help goes a long way.</p>
    <div class="social-links" role="group" aria-label="Social media">
        <?php foreach ($socialProfiles as [$platform, $glyph, $url, $domain]):
            $host = strtolower(parse_url($url, PHP_URL_HOST) ?: '');
            $validProfile = filter_var($url, FILTER_VALIDATE_URL) && parse_url($url, PHP_URL_SCHEME) === 'https' && ($host === $domain || str_ends_with($host, '.' . $domain));
        ?>
            <?php if ($validProfile): ?>
            <a class="social-icon" href="<?= escape($url) ?>" target="_blank" rel="noopener noreferrer" aria-label="Lion Home Services on <?= $platform ?> (opens in a new tab)" title="<?= $platform ?>"><?= icon($glyph) ?></a>
            <?php else: ?>
            <span class="social-icon social-icon--pending" role="img" aria-label="<?= $platform ?> profile link coming soon" title="<?= $platform ?> profile link coming soon"><?= icon($glyph) ?></span>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <span>&copy; <?= date('Y') ?> Lion Home Services</span>
</footer>
</body>
</html>
