@extends('layouts.admin')

@section('contents')
    <div class="w-full p-3" id="refresh">


        <div class="w-full lg:flex lg:gap-3">
            <div class="w-full lg:w-1/3 ts-gray-2 rounded-lg p-5 mb-3">
                <div class="w-full grid grid-cols-1 gap-3 p-2">

                    <a data-target="getting-started" role="button"
                        class="border-l-4 border-orange-500 text-purple-500 px-3 hover:scale-110 hover:text-purple-700 transition-all cursor-pointer rescron-card-trigger">
                        Getting Started</a>

                    <a data-target="pricing" role="button"
                        class="border-l-4 border-orange-500 px-3 hover:scale-110 hover:text-purple-700 transition-all cursor-pointer rescron-card-trigger">
                        Pricing</a>

                    <a data-target="system-overview" role="button"
                        class="border-l-4 border-orange-500 px-3 hover:scale-110 hover:text-purple-700 transition-all cursor-pointer rescron-card-trigger">
                        System Overview</a>

                    <a data-target="core" role="button"
                        class="border-l-4 border-orange-500 px-3 hover:scale-110 hover:text-purple-700 transition-all cursor-pointer rescron-card-trigger">
                        Core</a>

                    <a data-target="email" role="button"
                        class="border-l-4 border-orange-500 px-3 hover:scale-110 hover:text-purple-700 transition-all cursor-pointer rescron-card-trigger">
                        Email</a>

                    <a data-target="deposit" role="button"
                        class="border-l-4 border-orange-500 px-3 hover:scale-110 hover:text-purple-700 transition-all cursor-pointer rescron-card-trigger">
                        Deposit</a>

                    <a data-target="manual-wallet" role="button"
                        class="border-l-4 border-orange-500 px-3 hover:scale-110 hover:text-purple-700 transition-all cursor-pointer rescron-card-trigger">
                        Manual Wallet</a>

                    <a data-target="withdrawal" role="button"
                        class="border-l-4 border-orange-500 px-3 hover:scale-110 hover:text-purple-700 transition-all cursor-pointer rescron-card-trigger">
                        Withdrawal</a>


                    <a data-target="bot-setting" role="button"
                        class="border-l-4 border-orange-500 px-3 hover:scale-110 hover:text-purple-700 transition-all cursor-pointer rescron-card-trigger">
                        Bot</a>

                    <a data-target="dw-notification" role="button"
                        class="border-l-4 border-orange-500 px-3 hover:scale-110 hover:text-purple-700 transition-all cursor-pointer rescron-card-trigger">
                        DW Notification
                        <span
                            style="
                            display: inline-block;
                            background-color: #4CAF50;  /* green background */
                            color: #fff;                /* white text */
                            font-size: 12px;
                            font-weight: bold;
                            padding: 2px 6px;
                            border-radius: 4px;
                            text-transform: uppercase;
                            ">NEW
                        </span>
                    </a>


                    <a data-target="security" role="button"
                        class="border-l-4 border-orange-500 px-3 hover:scale-110 hover:text-purple-700 transition-all cursor-pointer rescron-card-trigger">
                        Security</a>


                    <a data-target="p2p" role="button"
                        class="border-l-4 border-orange-500 px-3 hover:scale-110 hover:text-purple-700 transition-all cursor-pointer rescron-card-trigger">
                        P2p</a>

                    <a data-target="contact" role="button"
                        class="border-l-4 border-orange-500 px-3 hover:scale-110 hover:text-purple-700 transition-all cursor-pointer rescron-card-trigger">
                        Contact</a>

                    <a data-target="seo" role="button"
                        class="border-l-4 border-orange-500 px-3 hover:scale-110 hover:text-purple-700 transition-all cursor-pointer rescron-card-trigger">
                        SEO</a>

                    <a data-target="referral" role="button"
                        class="border-l-4 border-orange-500 px-3 hover:scale-110 hover:text-purple-700 transition-all cursor-pointer rescron-card-trigger">
                        Referral</a>

                    <a data-target="cronjob" role="button"
                        class="border-l-4 border-orange-500 px-3 hover:scale-110 hover:text-purple-700 transition-all cursor-pointer rescron-card-trigger">
                        Cron Job</a>

                    <a data-target="misc" role="button"
                        class="border-l-4 border-orange-500 px-3 hover:scale-110 hover:text-purple-700 transition-all cursor-pointer rescron-card-trigger">
                        Misc</a>

                    <a data-target="telegram" role="button"
                        class="border-l-4 border-orange-500 px-3 hover:scale-110 hover:text-purple-700 transition-all cursor-pointer rescron-card-trigger">
                        Telegram Notification</a>


                    <a href="{{ route('admin.settings.templates.index') }}"
                        class="border-l-4 border-orange-500 px-3 hover:scale-110 hover:text-purple-700 transition-all cursor-pointer">
                        Templates</a>


                    <a href="{{ route('admin.binance.index') }}"
                        class="border-l-4 border-orange-500 px-3 hover:scale-110 hover:text-purple-700 transition-all cursor-pointer">
                        Binance Plugin</a>

                    <a href="{{ route('admin.backups.index') }}"
                        class="border-l-4 border-orange-500 px-3 hover:scale-110 hover:text-purple-700 transition-all cursor-pointer">
                        Backups</a>

                    <a href="{{ route('admin.settings.update.index') }}"
                        class="border-l-4 border-orange-500 px-3 hover:scale-110 hover:text-purple-700 transition-all cursor-pointer">
                        Update</a>

                </div>
            </div>
            <div class="w-full lg:w-2/3">
                {{-- getting started --}}
                @include('admin.settings.getting-started')
                {{-- system overview --}}
                @include('admin.settings.system-overview')
                {{-- system overview --}}
                @include('admin.settings.pricing')
                {{-- core --}}
                @include('admin.settings.core')
                {{-- email --}}
                @include('admin.settings.email')
                {{-- deposit --}}
                @include('admin.settings.deposit')
                {{-- manual wallet --}}
                @include('admin.settings.manual-wallet')
                {{-- withdrawal --}}
                @include('admin.settings.withdrawal')
                {{-- bot --}}
                @include('admin.settings.bot')
                {{-- dw notification --}}
                @include('admin.settings.dw-notification')
                {{-- security --}}
                @include('admin.settings.security')
                {{-- contact --}}
                @include('admin.settings.contact')
                {{-- p2p --}}
                @include('admin.settings.p2p')
                {{-- referral --}}
                @include('admin.settings.referral')
                {{-- cronjob --}}
                @include('admin.settings.cronjob')
                {{-- misc --}}
                @include('admin.settings.misc')
                {{-- seo --}}
                @include('admin.settings.seo')
                {{-- telegram --}}
                @include('admin.settings.telegram')

            </div>

        </div>
    </div>
@endsection
