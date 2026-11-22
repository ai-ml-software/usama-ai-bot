@extends('layouts.admin')

@section('contents')
    <div class="w-full p-3" >


        <div class="w-full lg:flex lg:gap-3">
            <div class="w-full lg:w-1/3 ts-gray-2 rounded-lg p-5 mb-3">
                <div class="w-full grid grid-cols-1 gap-3 p-2">

                    <a data-target="settings" role="button"
                        class="border-l-4 border-orange-500 text-purple-500 px-3 hover:scale-110 hover:text-purple-700 transition-all cursor-pointer rescron-card-trigger">
                        Set Up</a>

                    {{-- <a data-target="subscription" role="button"
                        class="border-l-4 border-orange-500 px-3 hover:scale-110 hover:text-purple-700 transition-all cursor-pointer rescron-card-trigger">
                        Manage Subscription</a> --}}

                    <a data-target="trades" role="button"
                        class="border-l-4 border-orange-500 px-3 hover:scale-110 hover:text-purple-700 transition-all cursor-pointer rescron-card-trigger">
                        Trading History</a>


                </div>
            </div>
            <div class="w-full lg:w-2/3">
                {{-- getting started --}}
                @include('binance::admin.settings')

                {{-- subscription --}}
                {{-- @include('binance::admin.subscription') --}}

                {{-- subscription --}}
                @include('binance::admin.recent')
                

            </div>

        </div>
    </div>
@endsection
