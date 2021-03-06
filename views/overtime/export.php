<?php


$sheet = $this->excel->setActiveSheetIndex(0);
$sheet->setTitle(mb_strimwidth(lang('overtime_export_title'), 0, 28, "...")); 
$sheet->setCellValue('A1', lang('overtime_export_thead_id'));
$sheet->setCellValue('B1', lang('overtime_export_thead_fullname'));
$sheet->setCellValue('C1', lang('overtime_export_thead_date'));
$sheet->setCellValue('D1', lang('overtime_export_thead_duration'));
$sheet->setCellValue('E1', lang('overtime_export_thead_cause'));
$sheet->setCellValue('F1', lang('overtime_export_thead_status'));
$sheet->getStyle('A1:F1')->getFont()->setBold(true);
$sheet->getStyle('A1:F1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

if ($filter == 'all') {
    $showAll = true;
} else {
    $showAll = false;
}
$requests = $this->overtime_model->requests($this->user_id, $showAll);
$line = 2;
foreach ($requests as $request) {
    $date = new DateTime($request['date']);
    $startdate = $date->format(lang('global_date_format'));
    $sheet->setCellValue('A' . $line, $request['id']);
    $sheet->setCellValue('B' . $line, $request['firstname'] . ' ' . $request['lastname']);
    $sheet->setCellValue('C' . $line, $startdate);
    $sheet->setCellValue('D' . $line, $request['duration']);
    $sheet->setCellValue('E' . $line, $request['cause']);
    $sheet->setCellValue('F' . $line, lang($request['status_name']));
    $line++;
}


foreach(range('A', 'F') as $colD) {
    $sheet->getColumnDimension($colD)->setAutoSize(TRUE);
}

$filename = 'overtime.xls';
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');
$objWriter = PHPExcel_IOFactory::createWriter($this->excel, 'Excel5');
$objWriter->save('php://output');
