<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Privacy · Wordis</title>
        <link rel="icon" href="/favicon.ico" sizes="32x32">
        <link rel="icon" href="/assets/favicon.svg" type="image/svg+xml">
        @vite(['resources/css/app.css'])
    </head>
    <body>
        <article class="policy">
            <a href="/"><img id="wordis-logo" src='/assets/wordis-logo.svg' alt='Wordis' width="4460" height="995" /></a>

            <h1>Privacy</h1>
            <p>Last updated 21 October 2026.</p>
            <p>Wordis is a daily word game run by Daniel Floris. This page explains what Wordis keeps about you, and why.</p>

            <h2>Playing without signing in</h2>
            <ul>
                <li>To keep your place while you play, the site sets a session cookie. The server stores that session with your IP address and browser details, and deletes it after 30 days without a visit.</li>
                <li>To stop guesses being sent faster than a person could type, your IP address is briefly used to count requests. The count is gone within a minute.</li>
                <li>Guesses that aren't in the dictionary are logged, without anything that identifies you, so the word list can be improved.</li>
            </ul>

            <h2>Signing in</h2>
            <p>Signing in is optional. It is handled by <a href="https://workos.com/privacy">WorkOS</a>, which stores your sign-in details and sends sign-in emails. Wordis never sees your password.</p>
            <p>When you sign in, Wordis keeps your name, your email address and your WorkOS account ID, so it can recognise you and keep your games with your account. If you sign in with Google or GitHub, they share your name and email address with WorkOS for this.</p>

            <h2>Who else handles your data</h2>
            <ul>
                <li><a href="https://workos.com/privacy">WorkOS</a>, for signing in.</li>
                <li><a href="https://cloud.laravel.com">Laravel Cloud</a>, which hosts the site and its database.</li>
                <li><a href="https://www.cloudflare.com/privacypolicy/">Cloudflare</a>, which passes your visits on to the site.</li>
            </ul>
            <p>There are no ads and no analytics, and your data is never sold.</p>

            <h2>Cookies</h2>
            <p>Wordis only sets the cookies it needs to work: one for your session and one that protects forms from forgery. There are no tracking cookies.</p>

            <h2>Seeing or deleting your data</h2>
            <p>Email <a href="mailto:daniel.j.floris@gmail.com">daniel.j.floris@gmail.com</a> to see, correct or delete what Wordis keeps about you. Deleting your account also deletes your WorkOS account.</p>

            <h2>Changes</h2>
            <p>Changes to this page will be posted here, with a new date at the top.</p>
        </article>
    </body>
</html>
