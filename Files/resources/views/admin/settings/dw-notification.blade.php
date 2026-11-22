
<div class="w-full p-5 mb-5 ts-gray-2 rounded-lg transition-all rescron-card hidden" id="dw-notification">
    <h3 class="capitalize  font-extrabold "><span class="border-b-2">DW Notification</span>
        
    </h3>
    <p>
        <span class="text-xs text-blue-500">
            This feature allows you to show random deposit and withdrawal activities popup on your site to
            increase user trust and engagement.
        </span>
    </p>




    <div class="w-full">
        <div class="grid grid-cols-1 gap-3 mt-5">


            <form action="{{ route('admin.settings.dw-notification') }}" method="POST" class="mt-5 gen-form" data-action="none"
                enctype="multipart/form-data">
                @csrf

                <div class="grid grid-cols-1 gap-5">


                    <div class="relative grid grid-cols-1 gap-5">

                        <div class="relative">
                            <input type="number" step="any" name="dw_notification_min_interval" placeholder="Minimum Interval (in mins)"
                                id="dw_notification_min_interval" class="theme1-text-input pl-3" required
                                value="{{ site('dw_notification_min_interval') }}">
                            <label for="dw_notification_min_interval" class="placeholder-label text-gray-300 ts-gray-2 px-2">Minimum
                                Interval (in mins)</label>
                            <span class="text-xs text-blue-500">
                                Minimum interval between two notifications
                            </span>
                        </div>

                        <div class="relative">
                            <input type="number" step="any" name="dw_notification_max_interval" placeholder="Maximum Interval (in mins)"
                                id="dw_notification_max_interval" class="theme1-text-input pl-3" required
                                value="{{ site('dw_notification_max_interval') }}">
                            <label for="dw_notification_max_interval" class="placeholder-label text-gray-300 ts-gray-2 px-2">Maximum
                                Interval (in mins)</label>
                            <span class="text-xs text-blue-500">
                                Maximum interval between two notifications
                            </span>
                        </div>

                        <div class="relative">


                            <select name="dw_notification_enabled" id="dw_notification_enabled" class="theme1-text-input pl-3" required>
                                <option value="0" @if (site('dw_notification_enabled') == 0) selected @endif> Disabled
                                </option>
                                <option value="1" @if (site('dw_notification_enabled') == 1) selected @endif> Enabled
                                </option>
                            </select>
                            <label for="dw_notification_enabled" class="placeholder-label text-gray-300 ts-gray-2 px-2">DW
                                Notification</label>
                            <span class="text-xs text-blue-500">
                                If you enable this, random deposit and withdrawal notifications will be shown on your site
                            </span>
                        </div>


                        <div class="grid grid-cols-1">
                        <label for="dw_notification_script" class="text-gray-300 ts-gray-2 px-2">DW Notification Script</label>
                        <div class="relative">
                            <textarea  name="dw_notification_script" placeholder="DW Notification Script" id="dw_notification_script"
                                class="theme1-textarea  pl-3 " rows="15">{!! json_decode(site('dw_notification')) !!}</textarea>

                        </div>
                    </div>


                    </div>


                    



                </div>

                

                <div class="w-full grid grid-cols-1 gap-5 mt-10 mb-10">
                    <button type="submit" class="bg-purple-500 px-2 py-1 rounded-full transition-all">Save
                        Changes </button>
                </div>

            </form>

        </div>


    </div>

</div>

