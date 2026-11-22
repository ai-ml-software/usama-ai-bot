<div class="w-full p-5 mb-5 ts-gray-2 rounded-lg transition-all rescron-card" id="settings">
    <h3 class="capitalize  font-extrabold "><span class="border-b-2">Binance API Setup</span>
    </h3>




    <div class="w-full">
        <div class="grid grid-cols-1 gap-3 mt-5">

            @if ($server_url)
                <form action="{{ route('admin.binance.setup') }}" class="mt-5 gen-form" enctype="multipart/form-data" method="POST">
                    @csrf

                    <div class="grid grid-cols-1 gap-5">
                        <div class="relative ">
                            <div
                                class="w-full ts-gray-3 p-2 rounded-lg border border-slate-800 hover:border-slate-600 mb-5">
                                @if (strtolower($server_location_info) == 'us')
                                    <p
                                        class="text-xs text-red-500 brounded-lg border border-slate-800 hover:border-slate-600 mb-3 p-3 rounded-lg">
                                        <span class="material-icons">error</span> Your server is hosted on a US data
                                        center.
                                        Binance does not allow trading from US data centers and IP addresses. Contact
                                        your
                                        hosting to change your server data center to outside US.
                                    </p>
                                @endif
                                <p class="mb-5">Binance requires that you whitelist your server IPs, while creating
                                    your
                                    API key,
                                    whitelist the following ip addreses. <a
                                        class="text-orange-500 uppercase underline text-xs"
                                        href="https://youtu.be/2NLF6eV2xhk?si=lhTggnf1-V4YXAtT" target="_blank"
                                        rel="noopener noreferrer">How to create API key? Watch Guide <span
                                            class="material-icons">link</span></a></p>
                                @foreach ($ips as $ip)
                                    <p class="text-orange-500 clipboard w-full flex space-x-2 cursor-pointer"
                                        data-copy="{{ demoMask($ip) }}">
                                        <span>{{ demoMask($ip) }}</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="w-6 h-6"
                                            viewBox="0 0 16 16">
                                            <path fill-rule="evenodd"
                                                d="M4 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V2Zm2-1a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H6ZM2 5a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1v-1h1v1a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h1v1H2Z" />
                                        </svg>
                                    </p>
                                @endforeach
                                <h3 class="capitalize text-xs mt-5"><span class="border-b-2">Required API
                                        Permisions</span>
                                </h3>
                                <ul class="text-xs">
                                    <li class="flex justify-start items-center"> <span>
                                            <svg data-slot="icon" fill="none" stroke-width="1.5"
                                                stroke="currentColor" class="w-3 h-3 text-orange-500"
                                                viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"
                                                aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M5.25 7.5A2.25 2.25 0 0 1 7.5 5.25h9a2.25 2.25 0 0 1 2.25 2.25v9a2.25 2.25 0 0 1-2.25 2.25h-9a2.25 2.25 0 0 1-2.25-2.25v-9Z">
                                                </path>
                                            </svg>
                                        </span>
                                        <span>Enabe Reading</span>
                                    </li>

                                    <li class="flex justify-start items-center"> <span>
                                            <svg data-slot="icon" fill="none" stroke-width="1.5"
                                                stroke="currentColor" class="w-3 h-3 text-orange-500"
                                                viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"
                                                aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M5.25 7.5A2.25 2.25 0 0 1 7.5 5.25h9a2.25 2.25 0 0 1 2.25 2.25v9a2.25 2.25 0 0 1-2.25 2.25h-9a2.25 2.25 0 0 1-2.25-2.25v-9Z">
                                                </path>
                                            </svg>
                                        </span>
                                        <span>Enabe Spot & Margin Trading</span>
                                    </li>

                                    <li class="flex justify-start items-center"> <span>
                                            <svg data-slot="icon" fill="none" stroke-width="1.5"
                                                stroke="currentColor" class="w-3 h-3 text-orange-500"
                                                viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"
                                                aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M5.25 7.5A2.25 2.25 0 0 1 7.5 5.25h9a2.25 2.25 0 0 1 2.25 2.25v9a2.25 2.25 0 0 1-2.25 2.25h-9a2.25 2.25 0 0 1-2.25-2.25v-9Z">
                                                </path>
                                            </svg>
                                        </span>
                                        <span>Enabe Futures</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="relative">
                            <input type="text" name="api_key" placeholder="API Key" id="api_key"
                                class="theme1-text-input pl-3" required value="{{ demoMask(env('BINANCE_API_KEY')) }}">
                            <label for="api_key" class="placeholder-label text-gray-300 ts-gray-2 px-2">API Key</label>
                            <span class="text-xs text-red-500">
                                @error('api_key')
                                    {{ $message }}
                                @enderror
                            </span>
                        </div>

                        <div class="relative">
                            <input type="text" name="secret_key" placeholder="Secret Key" id="secret_key"
                                class="theme1-text-input pl-3" required value="{{ demoMask(env('BINANCE_SECRET_KEY')) }}">
                            <label for="secret_key" class="placeholder-label text-gray-300 ts-gray-2 px-2">Secret
                                Key</label>
                            <span class="text-xs text-red-500">
                                @error('secret_key')
                                    {{ $message }}
                                @enderror
                            </span>
                        </div>

                        

                    </div>





                    <div class="w-full grid grid-cols-1 gap-5 mt-10 mb-10">
                        <button type="submit" class="bg-purple-500 px-2 py-1 rounded-full transition-all">Save
                            Changes </button>
                    </div>

                </form>
            @else
                <div class="relative">
                    <span class="text-xs text-red-500">
                        <span class="material-icons text-xs">warning</span> Error: Binance Plugin is not activated for {{ domain() }}
                    </span>
                </div>

            @endif



        </div>


    </div>

</div>



@push('scripts')
@endpush
