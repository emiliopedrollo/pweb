@extends('layouts.guest')

@section('content')

    @component('components.auth-card')
        @slot('logo')
            <div class="col-sm-6">
                <a href="/">
                    @include('components.application-logo', ['attributes' => 'height="9em" width="auto"  class="mt-3"'])
                </a>
            </div>
        @endslot

        <!-- Session Status -->
        @include('components.auth-session-status', ['status' => session('status'), 'class' => 'mb-4 text-danger'])

        <form method="POST" action="{{ route('signup') }}">
            @csrf

            <!-- Email Address -->
            <div class="mt-4">
                @include('components.input-label', ['attributes' => 'for="email"', 'value' => 'Email'])

                @include('components.text-input', ['attributes' =>
                    'id="email" class="form-control" type="email" name="email" value="'. old('email') .'" required'
                ])

                @include('components.input-error', ['messages' => errors('email'), 'class' => 'mt-2'])
            </div>

            <!-- Password -->
            <div class="mt-4">
                @include('components.input-label', ['attributes' => 'for="password"', 'value' => 'Password'])

                @include('components.text-input', ['attributes' =>
                    'id="password" class="form-control" type="password" name="password" required autocomplete="new-password"'
                ])

                @include('components.input-error', ['messages' => errors('password'), 'class' => 'mt-2'])
            </div>

            <!-- Confirm Password -->
            <div class="mt-4">
                @include('components.input-label', ['attributes' => 'for="password_confirmation"', 'value' => 'Confirm Password'])

                @include('components.text-input', ['attributes' =>
                    'id="password_confirmation" class="form-control" type="password" name="password_confirmation" required'
                ])

                @include('components.input-error', ['messages' => errors('password_confirmation'), 'class' => 'mt-2'])
            </div>

            <div class="d-flex justify-content-end align-content-center mt-4">
                <a class="mr-3 p-2" href="{{ route('login') }}">
                    Already registered?
                </a>

                @component('components.primary-button', ['class' => 'ml-3 px-3'])
                    Register
                @endcomponent
            </div>
        </form>
    @endcomponent
@endsection
