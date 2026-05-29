@auth
    @php
        $voiceSampleProfile = auth()->user()->profile()->first();
        $showVoiceSamplePrompt = !auth()->user()->is_admin && !optional($voiceSampleProfile)->voice_sample_completed_at;
    @endphp

    @if($showVoiceSamplePrompt)
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
                        <p class="text-sm font-semibold uppercase tracking-[.22em] text-amber-200/80">Tối ưu hóa bài học</p>
                        <h2 class="mt-2 text-2xl font-black sm:text-3xl">Ghi âm giọng nói của bạn</h2>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-300">
                            Hệ thống cần một đoạn ghi âm ngắn để tối ưu hóa trải nghiệm bài học. Bạn có thể bỏ qua bây giờ, nhưng popup này sẽ hiện lại cho đến khi bạn ghi âm xong và bấm hoàn thành.
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
                                Bắt đầu ghi âm
                            </button>
                            <button type="button" class="rounded-2xl border border-white/10 bg-white/10 px-5 py-3 font-bold text-slate-100 transition hover:bg-white/15 disabled:cursor-not-allowed disabled:opacity-50" @click="stopRecording()" :disabled="!isRecording || isBusy">
                                Dừng ghi âm
                            </button>
                        </div>

                        <template x-if="audioUrl">
                            <div class="mt-5 rounded-2xl border border-emerald-300/15 bg-emerald-400/10 p-4">
                                <p class="text-sm font-semibold text-emerald-100">Bản ghi tạm thời</p>
                                <audio class="mt-3 w-full" :src="audioUrl" controls></audio>
                            </div>
                        </template>

                        <template x-if="deleteCountdown">
                            <p class="mt-4 text-sm text-amber-100">
                                File ghi âm tạm thời sẽ tự xóa sau <span class="font-black" x-text="deleteCountdown"></span>.
                            </p>
                        </template>

                        <template x-if="message">
                            <p class="mt-4 text-sm" :class="error ? 'text-rose-200' : 'text-emerald-100'" x-text="message"></p>
                        </template>
                    </div>

                    <div class="rounded-[24px] border border-white/10 bg-white/[.04] p-5">
                        <p class="text-sm font-semibold uppercase tracking-[.18em] text-violet-200/70">Hướng dẫn</p>
                        <ol class="mt-4 space-y-3 text-sm leading-7 text-slate-300">
                            <li>1. Bấm bắt đầu ghi âm và nói vài câu bằng giọng bình thường.</li>
                            <li>2. Bấm dừng ghi âm để tải bản ghi lên hệ thống.</li>
                            <li>3. Nghe lại nếu cần, sau đó bấm hoàn thành để lưu trạng thái.</li>
                        </ol>

                        <div class="mt-6 grid gap-3">
                            <button type="button" class="rounded-2xl bg-gradient-to-r from-emerald-400 to-violet-500 px-5 py-4 font-black text-white shadow-glow transition hover:-translate-y-1 disabled:cursor-not-allowed disabled:opacity-50" @click="complete()" :disabled="!hasUploaded || isBusy">
                                Hoàn thành
                            </button>
                            <button type="button" class="rounded-2xl border border-white/10 bg-white/5 px-5 py-4 font-bold text-slate-200 transition hover:bg-white/10" @click="skip()">
                                Bỏ qua lúc này
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endauth
