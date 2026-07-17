@php
$signatures=$cmr->signatures->where('document_version',$cmr->version)->keyBy('signer_role');
$values=[
'company_serial'=>$cmr->company_serial?:$cmr->number,'consignor_name'=>$cmr->consignor_name,'consignor_address'=>$cmr->consignor_address,
'consignee_name'=>$cmr->consignee_name,'consignee_address'=>$cmr->consignee_address,'delivery_place'=>$cmr->delivery_place,
'taking_over_place'=>$cmr->taking_over_place,'taking_over_at'=>optional($cmr->taking_over_at)->format('Y-m-d H:i'),
'attached_documents'=>implode(', ',$cmr->attached_documents??[]),'goods_table'=>$cmr->goods->map(fn($g)=>$g->description.' | '.$g->package_count.' '.$g->package_type.' | '.$g->gross_weight_kg.' kg')->join("\n"),
'sender_instructions'=>$cmr->sender_instructions,'carrier_name'=>$cmr->carrier_name,'carrier_address'=>$cmr->carrier_address,
'driver_name'=>trim(($cmr->driver?->first_name_en??'').' '.($cmr->driver?->last_name_en??'')),'vehicle_plate'=>$cmr->fleet?->transit_plate,
'carrier_reservations'=>$cmr->carrier_reservations,'special_agreements'=>$cmr->special_agreements,
'charges'=>collect($cmr->charges??[])->map(fn($v,$k)=>ucfirst($k).': '.$v)->join(' | '),
'established_at'=>trim($cmr->established_at_place.' '.optional($cmr->established_at_date)->format('Y-m-d')),
'sender_signature'=>$signatures->get('sender')?->signer_name,'carrier_signature'=>$signatures->get('carrier')?->signer_name?:$signatures->get('driver')?->signer_name,
'consignee_signature'=>$signatures->get('consignee')?->signer_name,'verification_code'=>$cmr->verification_code,
];
@endphp
<!doctype html><html><head><meta charset="utf-8"><title>e-CMR {{ $cmr->company_serial }}</title><style>
@page{size:A4 portrait;margin:0}*{box-sizing:border-box}html,body{margin:0;padding:0}.sheet{position:relative;width:210mm;height:297mm;overflow:hidden;background:#fff}.background{position:absolute;inset:0;width:100%;height:100%;object-fit:fill}.field{position:absolute;z-index:2;overflow:hidden;white-space:pre-wrap;line-height:1.15;font-family:Arial,sans-serif}.toolbar{position:fixed;z-index:10;top:12px;right:12px}.toolbar button{border:0;border-radius:8px;padding:10px 16px;background:#047857;color:#fff;font-weight:bold}@media screen{body{display:flex;justify-content:center;padding:60px 20px 30px;background:#cbd5e1}.sheet{box-shadow:0 20px 50px #0f172a55}}@media print{.toolbar{display:none}}
</style></head><body><div class="toolbar"><button onclick="window.print()">Print / چاپ</button></div><div class="sheet"><img class="background" src="{{ asset('storage/'.$cmr->printTemplate->background_path) }}">@foreach($cmr->printTemplate->field_layout??[] as $field)@if($field['is_visible']??true)<div class="field" style="left:{{ $field['x_mm'] }}mm;top:{{ $field['y_mm'] }}mm;width:{{ $field['width_mm'] }}mm;height:{{ $field['height_mm'] }}mm;font-size:{{ $field['font_size_pt'] }}pt;font-weight:{{ ($field['is_bold']??false)?'700':'400' }};text-align:{{ $field['text_align']??'left' }}">{{ $values[$field['field_key']]??'' }}</div>@endif @endforeach</div></body></html>
