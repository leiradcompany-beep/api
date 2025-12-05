@extends('layouts.app')

@section('content')
    <div class="flex flex-col justify-center min-h-screen py-12 bg-gray-50 sm:px-6 lg:px-8">
        <div class="absolute top-0 right-0 mt-4 mr-4">
            @if (Route::has('login'))
                <div class="space-x-4">
                    @auth
                        <a
                            href="{{ url('/home') }}"
                            class="font-medium text-indigo-600 hover:text-indigo-500 focus:outline-none focus:underline transition ease-in-out duration-150"
                        >
                            Home
                        </a>
                    @else
                        <a
                            href="{{ route('login') }}"
                            class="font-medium text-indigo-600 hover:text-indigo-500 focus:outline-none focus:underline transition ease-in-out duration-150"
                        >
                            Log in
                        </a>

                        @if (Route::has('register'))
                            <a
                                href="{{ route('register') }}"
                                class="font-medium text-indigo-600 hover:text-indigo-500 focus:outline-none focus:underline transition ease-in-out duration-150"
                            >
                                Register
                            </a>
                        @endif
                    @endauth
                </div>
            @endif
        </div>

        <div class="flex items-center justify-center">
            <div class="flex flex-col justify-around">
                <div class="space-y-6">
                    <h1 class="text-5xl font-extrabold tracking-wider text-center text-gray-600">
                        {{ config('app.name') }}
                    </h1>

                    <example-component></example-component>
                </div>
            </div>
        </div>
    </div>
@endsection
