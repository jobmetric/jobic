<x-admin.layout title="داشبورد">
    <main class="cms-admin">
        <header class="cms-admin__header">
            <div>
                <p class="cms-admin__eyebrow">مدیریت {{ config('app.name', 'Jobic') }}</p>
                <h1>داشبورد</h1>
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="button button--primary" type="submit">خروج</button>
            </form>
        </header>

        <section class="cms-admin__welcome" aria-labelledby="welcome-heading">
            <h2 id="welcome-heading">خوش آمدید، {{ auth()->user()->name }}</h2>
            <p>این صفحه اصلی مدیریت Jobic است. دموی Vuero از مسیر <code>/vuero</code> جدا نگه داشته شده است.</p>
        </section>
    </main>
</x-admin.layout>
