<div class="container-fluid" style="background-color: rgba(13, 110, 253, 0.5)">
    <div class="container">
        <nav class="row-cols-12 navbar navbar-expand-sm">
            <div class="container-fluid">
                <a class="navbar-brand" href="{{ route(\App\Controllers\Auth::HOME) }}">
                    @include('components.application-logo', ['attributes' => 'height="3em"'])
                </a>

                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                        aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="navbar-collapse align-middle" id="navbarNav">
                    <ul class="navbar-nav me-auto">

                        @component('components.nav-text')
                            {{ 'Pubi' }}
                        @endcomponent

                    </ul>
                    @auth
                        @component('components.nav-text')
                            {{ Auth::user()->email }}
                        @endcomponent
                    @endauth

                    <ul class="navbar-nav">
                        <!-- Authentication -->
                        <!--suppress HtmlUnknownTag -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            @component('components.nav-link', [
                                'href' => route('logout'),
                                'attributes' => 'onclick="event.preventDefault();this.closest(\'form\').submit();"'
                            ]){{ 'Sair' }}@endcomponent
                            <x-nav-link :href="route('logout')">
                            </x-nav-link>
                        </form>
                    </ul>
                </div>

            </div>
        </nav>
    </div>
</div>
