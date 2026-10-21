<nav class="account">
    @auth
        <a href="{{ route('history') }}" aria-label="History" title="History" @if (request()->routeIs('history')) aria-current="page" @endif><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3.5" y="5" width="17" height="15.5" rx="2.5"/><path d="M3.5 10h17M8 3v4M16 3v4M8 14h2M14 14h2M8 17h2"/></svg></a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="signed-in" aria-label="Log out" title="Log out"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7"/></svg></button>
        </form>
    @else
        <a href="{{ route('login') }}" aria-label="Log in" title="Log in"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7"/></svg></a>
    @endauth
</nav>
