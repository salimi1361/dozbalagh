<div id="inventory_modal" class="hidden fixed inset-0 bg-slate-900/80 backdrop-blur-sm flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl max-w-lg w-full shadow-2xl overflow-hidden border border-slate-200">
        <div class="bg-slate-900 p-5 flex justify-between items-center text-white">
            <h3 class="font-black text-lg">🎫 تولید انبوه سریال دوزبلاغ</h3>
            <button onclick="closeInventoryModal()" class="text-slate-400 hover:text-white text-2xl">✕</button>
        </div>
        
        <form action="{{ route('admin.inventory.store') }}" method="POST">
            @csrf
            <div class="p-6 space-y-5">
                <div>
                    <label class="block text-slate-700 font-bold mb-2 text-sm">کشور مقصد <span class="text-rose-500">*</span></label>
                    <select name="country_id" required class="w-full p-3 border border-slate-300 rounded-xl bg-white">
                        <option value="">انتخاب کنید...</option>
                        @foreach($countries as $country)
                            <option value="{{ $country->id }}">{{ $country->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <input type="number" name="serial_start" id="serial_start" oninput="calculateTotal()" placeholder="شروع سریال" class="p-3 border rounded-xl">
                    <input type="number" name="serial_end" id="serial_end" oninput="calculateTotal()" placeholder="پایان سریال" class="p-3 border rounded-xl">
                </div>
                <div class="bg-indigo-50 p-4 rounded-xl flex justify-between font-bold text-indigo-900">
                    <span>تعداد کل:</span> <span id="total_display">0</span>
                </div>
                <input type="date" name="expiry_date" class="w-full p-3 border rounded-xl">
            </div>
            <div class="p-5 border-t flex justify-end gap-3">
                <button type="button" onclick="closeInventoryModal()" class="px-5 py-2.5 rounded-xl font-bold text-slate-600">انصراف</button>
                <button type="submit" class="bg-indigo-600 text-white px-6 py-2.5 rounded-xl font-bold">ثبت نهایی</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openInventoryModal() { document.getElementById('inventory_modal').classList.remove('hidden'); }
    function closeInventoryModal() { document.getElementById('inventory_modal').classList.add('hidden'); }
    
    function calculateTotal() {
        let start = parseInt(document.getElementById('serial_start').value) || 0;
        let end = parseInt(document.getElementById('serial_end').value) || 0;
        document.getElementById('total_display').innerText = (end >= start) ? (end - start + 1).toLocaleString() : 0;
    }
</script>