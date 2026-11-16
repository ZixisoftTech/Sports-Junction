<?php
$baseUrl = \App\Core\Response::baseUrl();
?>

<!-- Hero Header Section -->
<section class="py-8 bg-slate-900 text-white border-b border-slate-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="max-w-3xl">
            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-500/20 text-amber-300 text-xs font-extrabold uppercase tracking-widest mb-3 border border-amber-500/30">
                <span class="material-symbols-outlined text-sm">support_agent</span> DIRECT B2B DESK
            </span>
            <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-white mb-3">Contact Sports Junction Help Center</h1>
            <p class="text-slate-300 text-sm sm:text-base leading-relaxed">
                Have a sports manufacturing requirement, bulk procurement question, or vendor partnership enquiry? Send a message directly to our centralized B2B Trade Desk.
            </p>
        </div>
    </div>
</section>

<!-- Main Help Center Content Section -->
<section class="py-12 bg-slate-50 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            <!-- Left Column: How Sports Junction Works & Custom Factory Quote -->
            <div class="lg:col-span-5 space-y-6">
                <!-- How It Works Card -->
                <div class="bg-white rounded-2xl p-6 sm:p-7 shadow-sm border border-slate-200">
                    <h3 class="text-lg font-extrabold text-slate-900 mb-4 flex items-center gap-2">
                        <span>How Sports Junction Works</span>
                    </h3>
                    <div class="space-y-4 text-sm text-slate-600">
                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-blue-700 text-xl mt-0.5">schedule</span>
                            <div>
                                <div class="font-bold text-slate-900">Rapid Response Time</div>
                                <div class="text-xs leading-relaxed text-slate-600 mt-0.5">Our team responds within 24–48 business hours.</div>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-blue-700 text-xl mt-0.5">storefront</span>
                            <div>
                                <div class="font-bold text-slate-900">Direct Factory Connections</div>
                                <div class="text-xs leading-relaxed text-slate-600 mt-0.5">For urgent sourcing inquiries, register as a buyer to connect directly with manufacturers.</div>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="material-symbols-outlined text-blue-700 text-xl mt-0.5">verified_user</span>
                            <div>
                                <div class="font-bold text-slate-900">Verified Quality Standards</div>
                                <div class="text-xs leading-relaxed text-slate-600 mt-0.5">All listed manufacturing units comply with verified industrial and export specifications.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Custom Factory Quote / Register Card -->
                <div class="bg-slate-900 text-white rounded-2xl p-6 sm:p-7 shadow-sm border border-slate-800">
                    <h4 class="font-extrabold text-base mb-2 text-white">
                        Looking for a Custom Factory Quote?
                    </h4>
                    <p class="text-slate-300 text-xs leading-relaxed mb-6">
                        All inquiries submitted through this form are logged directly into our Centralized Enquiry Management system with a unique tracking ID.
                    </p>
                    <a href="<?= htmlspecialchars($baseUrl) ?>/register" class="w-full py-3 px-4 rounded-xl bg-orange-600 hover:bg-orange-500 text-white font-extrabold text-xs uppercase tracking-wider transition text-center block shadow-md">
                        REGISTER AS BUYER
                    </a>
                </div>
            </div>

            <!-- Right Column: Buyer Help & Seller Help Portals -->
            <div class="lg:col-span-7 space-y-6">
                <!-- For Buyer Help Card -->
                <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-slate-200">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="material-symbols-outlined text-blue-700 text-2xl">shopping_bag</span>
                        <h2 class="text-xl font-extrabold text-slate-900">For Buyer Help</h2>
                    </div>
                    <p class="text-slate-600 text-sm leading-relaxed mb-6">
                        Have a sports manufacturing requirement, bulk procurement question, or vendor partnership enquiry? Send a message directly to our centralized B2B Trade Desk.
                    </p>
                    <div class="flex flex-wrap items-center gap-3">
                        <a href="<?= htmlspecialchars($baseUrl) ?>/buyer-help-centre" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-[#00288e] hover:bg-blue-950 text-white font-extrabold text-xs uppercase tracking-wider transition shadow-sm">
                            <span>SUBMIT CONTACT ENQUIRY</span>
                            <span class="material-symbols-outlined text-base">arrow_forward</span>
                        </a>
                        <span class="text-xs text-slate-400">Order issues • Product sourcing • Payment support</span>
                    </div>
                </div>

                <!-- For Seller Help Card -->
                <div class="bg-white rounded-2xl p-6 sm:p-8 shadow-sm border border-slate-200">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="material-symbols-outlined text-blue-700 text-2xl">factory</span>
                        <h2 class="text-xl font-extrabold text-slate-900">For Seller Help</h2>
                    </div>
                    <p class="text-slate-600 text-sm leading-relaxed mb-6">
                        Have a sports manufacturing requirement, bulk procurement question, or vendor partnership enquiry? Send a message directly to our centralized B2B Trade Desk.
                    </p>
                    <div class="flex flex-wrap items-center gap-3">
                        <a href="<?= htmlspecialchars($baseUrl) ?>/seller-help-centre" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-[#00288e] hover:bg-blue-950 text-white font-extrabold text-xs uppercase tracking-wider transition shadow-sm">
                            <span>SUBMIT CONTACT ENQUIRY</span>
                            <span class="material-symbols-outlined text-base">arrow_forward</span>
                        </a>
                        <span class="text-xs text-slate-400">Manufacturer onboarding • Catalog uploads • Seller desk</span>
                    </div>
                </div>

                <!-- Quick Help Center Info Box -->
                <div class="p-4 rounded-2xl bg-blue-50/60 border border-blue-100 flex items-start gap-3 text-xs text-slate-600">
                    <span class="material-symbols-outlined text-blue-700 text-lg mt-0.5">info</span>
                    <div>
                        <span class="font-bold text-slate-800">Existing Query or Ticket?</span>
                        <span class="ml-1">You can enter your existing Query ID (e.g., SJ-BUY-2026-00001 or SJ-SEL-2026-00001) in either Help Centre form to look up or update your support ticket.</span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>
