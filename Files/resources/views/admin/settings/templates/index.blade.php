@php
    function templateInfo($template)
    {
       
        $path = resource_path('views/templates/' . $template . '/config.json');
        if (!file_exists($path)) {
            return [];
        }

        $config = file_get_contents($path);
        $config = json_decode($config);
        return $config;
    }
    // dd(templateInfo('valent')->name);
@endphp

@extends('layouts.admin')

@section('contents')
    <div class="w-full" id="refresh">
        <div class="w-full p-5 mb-5  rounded-lg transition-all rescron-card" id="templates">


            <div class="w-full ">
                <div class="grid grid-cols-1 gap-3 mt-5">
                    <div class="flex justify-end mb-5">
                        <div class="flex justify-end items-center ts-gray-2  mb-2 mt-5">
                            <div class="relative">

                                <span class="theme1-input-icon material-icons">
                                    search
                                </span>
                                <input type="text" placeholder="template" id="search-templates-input"
                                    class="theme1-text-input rounded-0" value="{{ request()->s }}">
                                <label for="search-templates-input"
                                    class="placeholder-label text-gray-300 ts-gray-2 px-2">Title
                                </label>

                            </div>
                            <div class="simple-pagination" data-paginator="templates">
                                <a id="search-templates-button" href="#"
                                    class="paginator-link px-3 py-2 bg-purple-500 hover:scale-110 transition-all"
                                    data-link="{{ route('admin.pages.index') }}" href="">Search</a>
                            </div>
                        </div>
                    </div>
                    <div class="w-full border-b border-slate-800 hover:border-slate-600"></div>
                    <div class="w-full grid grid-cols-1 lg:grid-cols-3 gap-3">
                        @forelse ($remote_templates as $template)
                            <div class="w-full ts-gray-2 rounded-lg p-5">
                                <div class="w-full h-80 overflow-hidden">
                                    <img src="{{ $template->previewImageLink }}" alt="preview screenshot">
                                </div>
                                @if ($template->name == site('template'))
                                    <div class="w-full flex items-center justify-center mb-2">
                                        <span class="text-2xl border border-slate-800 rounded-full px-5 py-1">Active</span>
                                    </div>
                                @endif

                                <div class="grid grid-cols-2">
                                    <p class="text-blue-500 font-mono text-xs">Name:</p>
                                    <p>{{ $template->longName }}</p>
                                    <p class="text-blue-500 font-mono text-xs">Version:</p>
                                    <p>{{ $template->version }}</p>
                                    <p class="text-blue-500 font-mono text-xs">Live Preview:</p>
                                    <p><a href="{{ $template->previewLink }}" target="_blank"
                                            rel="noopener noreferrer"><span class="border-b-2 text-orange-500">Click To
                                                View</span></a></p>
                                </div>


                                <div class="w-full flex justify-start items-center space-x-3 items-center mt-5">
                                    @if (!in_array($template->name, $local_templates))
                                        <div>
                                            <a href="{{ route('admin.settings.templates.download', ['template' => $template->name, 'version' => $template->version]) }}"
                                                class="cursor-pointer flex items-center bg-blue-500 px-2 py rounded-lg hover:scale-110 transition-all ">Download</a>
                                        </div>
                                    @else
                                        @if (site('template') !== $template->name)
                                            {{-- <a role="button" href="#" target="_blank"
                                                class="cursor-pointer flex items-center bg-blue-500 px-2 py rounded-lg hover:scale-110 transition-all ">Activate</a> --}}

                                            <div>
                                                <form action="{{ route('admin.settings.templates.activate') }}"
                                                    method="POST" class="gen-form" data-action="reload"
                                                    enctype="multipart/form-data">
                                                    @csrf
                                                    <input type="hidden" name="template" value="{{ $template->name }}">

                                                    <button type="submit"
                                                        class="bg-blue-500 px-2 py-1 rounded-lg hover:scale-110 transition-all transition-all">Activate</button>

                                                </form>
                                            </div>
                                        @endif
                                    @endif




                                    @if ($template->name !== 'default')
                                        @if (in_array($template->name, $local_templates))
                                            <div>
                                                {{-- str_replace('.', '', $template->version) > str_replace('.', '', templateInfo($template->name)->version) --}}
                                                @if (str_replace('.', '', $template->version) > str_replace('.', '', templateInfo($template->name)->version))
                                                    <a role="button"
                                                        href="{{ route('admin.settings.templates.download', ['template' => $template->name, 'version' => $template->version]) }}"
                                                        class="cursor-pointer flex items-center bg-blue-500 px-2 py rounded-lg hover:scale-110 transition-all ">Update</a>
                                                @else
                                                    <a role="button"
                                                        href="{{ route('admin.settings.templates.download', ['template' => $template->name, 'version' => $template->version]) }}"
                                                        class="cursor-pointer flex items-center bg-purple-500 px-2 py rounded-lg hover:scale-110 transition-all ">Re-install</a>
                                                @endif

                                            </div>

                                            <div>
                                                <form action="{{ route('admin.settings.templates.delete') }}"
                                                    method="POST" class="gen-form" data-action="reload"
                                                    enctype="multipart/form-data">
                                                    @csrf
                                                    <input type="hidden" name="template" value="{{ $template->name }}">

                                                    <button type="submit"
                                                        class="bg-red-500 px-2 py-1 rounded-lg hover:scale-110 transition-all transition-all">Delete</button>

                                                </form>
                                            </div>
                                        @endif
                                    @endif

                                </div>
                            </div>

                        @empty
                            <div
                                class="w-full flex justify-center items-center ts-gray-3 p-2 rounded-lg border border-slate-800 hover:border-slate-600 cursor-pointer">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-orange-500" fill="currentColor"
                                    class="bi bi-exclamation-triangle-fill" viewBox="0 0 16 16">
                                    <path
                                        d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z" />
                                </svg>
                                <span>Error fetching templates, if this error persist, contact support</span>
                            </div>
                        @endforelse
                    </div>







                </div>



            </div>

        </div>
    </div>
@endsection

@section('scripts')
    {{-- <script>
        // search deposit
        $(document).on('input keyup', '#search-templates-input', function(e) {
            var title = $(this).val();
            var base_link = $('#search-templates-button').data('link');
            var encodedRef = encodeURIComponent(ref);

            // Append the query parameter to the URL
            var link = base_link + '?s=' + encodedRef;
            $('#search-templates-button').attr('href', link);
        });
    </script> --}}
@endsection
