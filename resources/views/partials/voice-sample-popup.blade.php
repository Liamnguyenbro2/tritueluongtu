@auth
    @php
        $voiceSampleProfile = auth()->user()->profile()->first();
        $showVoiceSamplePrompt = !auth()->user()->is_admin && !optional($voiceSampleProfile)->voice_sample_completed_at;
        $voiceSampleText = [
            'eyebrow' => 'T&#7889;i &#432;u h&#243;a b&#224;i h&#7885;c',
            'title' => 'Ghi &#226;m gi&#7885;ng n&#243;i c&#7911;a b&#7841;n',
            'description' => 'H&#7879; th&#7889;ng c&#7847;n m&#7897;t &#273;o&#7841;n ghi &#226;m ng&#7855;n &#273;&#7875; t&#7889;i &#432;u h&#243;a tr&#7843;i nghi&#7879;m b&#224;i h&#7885;c. B&#7841;n c&#243; th&#7875; b&#7887; qua b&#226;y gi&#7901;, nh&#432;ng popup n&#224;y s&#7869; hi&#7879;n l&#7841;i cho &#273;&#7871;n khi b&#7841;n ghi &#226;m xong v&#224; b&#7845;m ho&#224;n th&#224;nh.',
            'start' => 'B&#7855;t &#273;&#7847;u ghi &#226;m',
            'stop' => 'D&#7915;ng ghi &#226;m',
            'temp_recording' => 'B&#7843;n ghi t&#7841;m th&#7901;i',
            'delete_after' => 'File ghi &#226;m t&#7841;m th&#7901;i s&#7869; t&#7921; x&#243;a sau',
            'complete' => 'Ho&#224;n th&#224;nh',
            'skip' => 'B&#7887; qua l&#250;c n&#224;y',
            'guide' => 'H&#432;&#7899;ng d&#7851;n',
            'guide_1' => '1. B&#7845;m b&#7855;t &#273;&#7847;u ghi &#226;m v&#224; n&#243;i v&#224;i c&#226;u b&#7857;ng gi&#7885;ng b&#236;nh th&#432;&#7901;ng.',
            'guide_2' => '2. B&#7845;m d&#7915;ng ghi &#226;m &#273;&#7875; t&#7843;i b&#7843;n ghi l&#234;n h&#7879; th&#7889;ng.',
            'guide_3' => '3. Nghe l&#7841;i n&#7871;u c&#7847;n, sau &#273;&#243; b&#7845;m ho&#224;n th&#224;nh &#273;&#7875; l&#432;u tr&#7841;ng th&#225;i.',
        ];
    @endphp

    @if ($showVoiceSamplePrompt)
        <div
            x-data="voiceSamplePrompt({
                hasUploaded: @js((bool) optional($voiceSampleProfile)->voice_sample_path),
                deleteAfterAt: @js(optional($voiceSampleProfile?->voice_sample_delete_after_at)->toIso8601String()),
                uploadUrl: @js(route('voice-sample.store')),
                completeUrl: @js(route('voice-sample.complete')),
                csrfToken: @js(csrf_token()),
            })"
            x-show="open"
            x-cloak
            class="fixed inset-0 z-[90] grid place-items-center bg-black/75 p-4 backdrop-blur-md"
        >
            <div class="glass w-full max-w-2xl rounded-[32px] p-6 shadow-glow sm:p-8">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-[.22em] text-amber-200/80">{!! html_entity_decode($voiceSampleText['eyebrow']) !!}</p>
                        <h2 class="mt-2 text-2xl font-black sm:text-3xl">{!! html_entity_decode($voiceSampleText['title']) !!}</h2>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-300">
                            {!! html_entity_decode($voiceSampleText['description']) !!}
                        </p>
                    </div>
                    <button type="button" class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl border border-white/10 bg-white/5 transition hover:bg-white/10" @click="skip()">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>

                <div class="mt-6 grid gap-4 lg:grid-cols-[1.2fr_.8fr]">
                    <div class="rounded-[24px] border border-white/10 bg-black/20 p-5">
                        <div class="flex flex-wrap items-center gap-3">
                            <div class="inline-flex items-center gap-2 rounded-full px-3 py-2 text-xs font-bold uppercase tracking-[.18em]"
                                :class="isRecording ? 'bg-rose-500/15 text-rose-100' : 'bg-white/5 text-slate-300'">
                                <span class="h-2 w-2 rounded-full" :class="isRecording ? 'bg-rose-300' : 'bg-slate-500'"></span>
                                <span x-text="isRecording ? 'Đang ghi âm' : 'Sẵn sàng'"></span>
                            </div>
                            <div class="rounded-full border border-white/10 bg-white/5 px-3 py-2 text-xs font-semibold text-slate-300">
                                <span x-text="timer"></span>
                            </div>
                        </div>

                        <div class="mt-5 flex flex-wrap gap-3">
                            <button type="button" class="rounded-2xl bg-gradient-to-r from-rose-500 to-fuchsia-500 px-5 py-3 font-black text-white shadow-glow transition hover:-translate-y-1 disabled:cursor-not-allowed disabled:opacity-50" @click="startRecording()" :disabled="isRecording || isBusy">
                                {!! html_entity_decode($voiceSampleText['start']) !!}
                            </button>
                            <button type="button" class="rounded-2xl border border-white/10 bg-white/10 px-5 py-3 font-bold text-slate-100 transition hover:bg-white/15 disabled:cursor-not-allowed disabled:opacity-50" @click="stopRecording()" :disabled="!isRecording || isBusy">
                                {!! html_entity_decode($voiceSampleText['stop']) !!}
                            </button>
                        </div>

                        <template x-if="audioUrl">
                            <div class="mt-5 rounded-2xl border border-emerald-300/15 bg-emerald-400/10 p-4">
                                <p class="text-sm font-semibold text-emerald-100">{!! html_entity_decode($voiceSampleText['temp_recording']) !!}</p>
                                <audio class="mt-3 w-full" :src="audioUrl" controls></audio>
                            </div>
                        </template>

                        <template x-if="deleteCountdown">
                            <p class="mt-4 text-sm text-amber-100">
                                {!! html_entity_decode($voiceSampleText['delete_after']) !!} <span class="font-black" x-text="deleteCountdown"></span>.
                            </p>
                        </template>

                        <template x-if="message">
                            <p class="mt-4 text-sm" :class="error ? 'text-rose-200' : 'text-emerald-100'" x-text="message"></p>
                        </template>

                        <div class="mt-5 grid gap-3 sm:hidden">
                            <button type="button" class="rounded-2xl bg-gradient-to-r from-emerald-400 to-violet-500 px-5 py-4 font-black text-white shadow-glow transition hover:-translate-y-1 disabled:cursor-not-allowed disabled:opacity-50" @click="complete()" :disabled="!hasUploaded || isBusy">
                                {!! html_entity_decode($voiceSampleText['complete']) !!}
                            </button>
                            <button type="button" class="rounded-2xl border border-white/10 bg-white/5 px-5 py-4 font-bold text-slate-200 transition hover:bg-white/10" @click="skip()">
                                {!! html_entity_decode($voiceSampleText['skip']) !!}
                            </button>
                        </div>
                    </div>

                    <div class="hidden rounded-[24px] border border-white/10 bg-white/[.04] p-5 sm:block">
                        <p class="text-sm font-semibold uppercase tracking-[.18em] text-violet-200/70">{!! html_entity_decode($voiceSampleText['guide']) !!}</p>
                        <ol class="mt-4 space-y-3 text-sm leading-7 text-slate-300">
                            <li>{!! html_entity_decode($voiceSampleText['guide_1']) !!}</li>
                            <li>{!! html_entity_decode($voiceSampleText['guide_2']) !!}</li>
                            <li>{!! html_entity_decode($voiceSampleText['guide_3']) !!}</li>
                        </ol>

                        <div class="mt-6 grid gap-3">
                            <button type="button" class="rounded-2xl bg-gradient-to-r from-emerald-400 to-violet-500 px-5 py-4 font-black text-white shadow-glow transition hover:-translate-y-1 disabled:cursor-not-allowed disabled:opacity-50" @click="complete()" :disabled="!hasUploaded || isBusy">
                                {!! html_entity_decode($voiceSampleText['complete']) !!}
                            </button>
                            <button type="button" class="rounded-2xl border border-white/10 bg-white/5 px-5 py-4 font-bold text-slate-200 transition hover:bg-white/10" @click="skip()">
                                {!! html_entity_decode($voiceSampleText['skip']) !!}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endauth
