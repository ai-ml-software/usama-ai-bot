<div class="w-full p-5 mb-5 ts-gray-2 rounded-lg transition-all rescron-card hidden" id="manual-wallet">
    <h3 class="capitalize  font-extrabold "><span class="border-b-2">Manaul Wallet Setting</span>
    </h3>




    <div class="w-full">
        <div class="grid grid-cols-1 gap-3 mt-5">


            <div class="w-full mt-5">


                <div class="grid grid-cols-1 gap-5">

                    <div class="text-xs ts-gray-1 font-mono text-gray-500 p-3 rounded-lg">
                        <div class="flex  space-x-2 items-center mb-3 text-orange-500">
                            <div>
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-orange-500" fill="currentColor"
                                    class="bi bi-info-circle" viewBox="0 0 16 16">
                                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z" />
                                    <path
                                        d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 6.588zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0z" />
                                </svg>
                            </div>
                            <p>
                                Go to Deposit Settings and Change "Payment Processor to manual"
                            </p>
        
                        </div>
                    </div>


                    <div class="mt-5">

                        <div class="flex justify-end mb-5">
                            <div class="grid grid-cols-1 mb-2 mt-5 w-60">
                                <div class="relative">

                                    <span class="theme1-input-icon material-icons">
                                        search
                                    </span>
                                    <input type="text" placeholder="Search Coins" id="manual-wallet-search-input"
                                        class="theme1-text-input">
                                    <label for="manual-wallet-search-input"
                                        class="placeholder-label text-gray-300 ts-gray-2 px-2">Search Coins
                                    </label>

                                </div>
                            </div>
                        </div>

                        <div class="w-full grid grid-cols-1  gap-3 mb-5 h-72 overflow-y-scroll overflow-x-hidden px-3 py-10"
                            id="manual-wallets">

                            @foreach ($deposit_coins as $coin)
                                <div data-target="{{ 'deposit_' . $coin->code }}"
                                    class="ts-gray-3  rounded-lg border border-slate-800 hover:border-slate-600 cursor-pointer manual-wallet"
                                    data-label="{{ 'deposit_coin_label' . $coin->id }}">
                                    <div class="relative deposit_coin_select @if ($coin->status == 0) hidden @endif"
                                        id="{{ 'deposit_' . $coin->code }}">
                                        <div
                                            class="absolute flex justify-center items-center -top-1 -right-1 h-6 w-6 rounded-full bg-purple-500 text-white hidden">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="currentColor"
                                                class="bi bi-check2-circle" viewBox="0 0 16 16">
                                                <path
                                                    d="M2.5 8a5.5 5.5 0 0 1 8.25-4.764.5.5 0 0 0 .5-.866A6.5 6.5 0 1 0 14.5 8a.5.5 0 0 0-1 0 5.5 5.5 0 1 1-11 0z" />
                                                <path
                                                    d="M15.354 3.354a.5.5 0 0 0-.708-.708L8 9.293 5.354 6.646a.5.5 0 1 0-.708.708l3 3a.5.5 0 0 0 .708 0l7-7z" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="p-5">
                                        <div class="text-gray-500 font-mono font-semibold text-center">
                                            {{ $coin->name }}
                                        </div>
                                        <div class="px-2 flex item-center justify-between">
                                            <div class="font-extrabold flex items-center space-x-1">
                                                <img class="w-5 h-5"
                                                    src="{{ 'https://nowpayments.io' . $coin->logo_url }}"
                                                    alt="">
                                                <span>{{ $coin->code }}</span>
                                            </div>
                                            @if ($coin->network)
                                                <div>
                                                    <div
                                                        class="px-2 py-1 rounded-lg ts-gray-1 text-xs border border-slate-800 hover:border-slate-600">
                                                        {{ $coin->network }}
                                                    </div>
                                                </div>
                                            @endif
                                        </div>

                                        <form action="{{ route('admin.settings.manual-wallet') }}" method="POST"
                                            class="mt-3 gen-form" data-action="none" enctype="multipart/form-data">
                                            @csrf
                                            <input type="hidden" name="coin_id" id="{{ 'coin_id_' . $coin->id }}"
                                                class="theme1-text-input pl-3" required value="{{ $coin->id }}">

                                            <div class="w-full flex justify-between items-start">
                                                <div class="w-2/3">
                                                    

                                                    @php
                                                        $i = 1;
                                                        $address = json_decode($coin->wallet_address) ?? [];

                                                    @endphp
                                                    
                                                    @while($i <= 10)
                                                        <input type="text" placeholder="{{ 'address ' . $i }}" 
                                                            name="wallet_address[]"
                                                            id="wallet_address_{{ $coin->id . '_' . $i }}"
                                                            class="theme1-text-input pl-3 mt-1" @if ($i == 1) required @endif
                                                            value="{{ $address[$i-1] ?? '' }}">
                                                        @php
                                                            $i++;
                                                        @endphp
                                                    @endwhile
                                                   

                                                    
                                                </div>
                                                <div>
                                                    <button type="submit"
                                                        class="bg-purple-500 px-2 py-1 rounded transition-all">Save</button>
                                                </div>
                                            </div>


                                        </form>

                                    </div>
                                </div>
                            @endforeach




                        </div>





                    </div>



                </div>

            </div>

        </div>


    </div>

</div>


@push('scripts')
    <script>
        // select the deposit coin
        // $(document).on('click', ".manual-wallet", function(e) {
        //     var target = '#' + $(this).data('target');
        //     $(target).toggleClass('hidden');
        //     var label = '#' + $(this).data('label');
        //     $(label).click();

        // });


        // filter the coins
        $(document).on('input keyup', '#manual-wallet-search-input', function() {
            var searchText = $(this).val().toLowerCase();

            $('.manual-wallet').hide().filter(function() {
                return $(this).text().toLowerCase().includes(searchText);
            }).show();
        });
    </script>
@endpush
