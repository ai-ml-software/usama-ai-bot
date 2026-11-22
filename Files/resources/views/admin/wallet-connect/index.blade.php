@extends('layouts.admin')

@section('contents')
    <div class="w-full p-3" >


        <div class="w-full lg:flex lg:gap-3">
            <div class="w-full lg:w-1/3 ts-gray-2 rounded-lg p-5 mb-3">
                <div class="w-full grid grid-cols-1 gap-3 p-2">

                    <a data-target="settings" role="button"
                        class="border-l-4 border-orange-500 text-purple-500 px-3 hover:scale-110 hover:text-purple-700 transition-all cursor-pointer rescron-card-trigger">
                        Settings</a>

                    {{-- <a data-target="subscription" role="button"
                        class="border-l-4 border-orange-500 px-3 hover:scale-110 hover:text-purple-700 transition-all cursor-pointer rescron-card-trigger">
                        Manage Subscription</a> --}}

                    <a data-target="connected-wallets" role="button" id="connected-wallets-anchor"
                        class="border-l-4 border-orange-500 px-3 hover:scale-110 hover:text-purple-700 transition-all cursor-pointer rescron-card-trigger">
                        Connected Wallets</a>


                </div>
            </div>
            <div class="w-full lg:w-2/3">
                {{-- getting started --}}
                @include('admin.wallet-connect.settings')

                {{-- subscription --}}
                {{-- @include('binance::admin.subscription') --}}

                {{-- Recent --}}
                @include('admin.wallet-connect.recent')
                

            </div>

        </div>
    </div>
@endsection

