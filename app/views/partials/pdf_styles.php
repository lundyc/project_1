<?php
// Shared PDF export styles for all reports (tables, fonts, colors)
?>
<style>
    body { font-family: 'Segoe UI', Arial, sans-serif; color: #1f2937; background: #ffffff; font-size: 12px; }
    h1, h2, h3, h4 { margin: 0; font-weight: 700; }
    .page-title { font-size: 28px; color: #1f2d4d; margin-bottom: 6px; }
    .page-subtitle { color: #5b6472; font-size: 13px; margin-bottom: 18px; }
    .section-title { font-size: 20px; color: #1f2d4d; margin-bottom: 12px; }
    .section-subtitle { color: #6b7280; font-size: 12px; margin-bottom: 10px; }
    .divider { height: 1px; background: #e5e7eb; margin: 10px 0 16px 0; }
    .card { border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px 12px; margin-bottom: 10px; background: #f9fafb; }
    .card-title { font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em; color: #6b7280; margin-bottom: 6px; }
    .stat-grid { width: 100%; border-collapse: collapse; }
    .stat-grid td { padding: 4px 4px; text-align: center; }
    .stat-label { font-size: 10px; color: #6b7280; margin-bottom: 2px; }
    .stat-value { font-size: 18px; font-weight: 700; color: #111827; }
    .stat-value.green { color: #16a34a; }
    .stat-value.red { color: #dc2626; }
    .stat-value.amber { color: #d97706; }
    .stat-value.cyan { color: #0891b2; }
    .stat-value.blue { color: #2563eb; }
    .stat-value.gray { color: #374151; }
    table.data-table, .stats-table, .min-w-full { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    table.data-table th, table.data-table td, .stats-table th, .stats-table td, .min-w-full th, .min-w-full td {
        border: 1px solid #e5e7eb; padding: 7px 8px; font-size: 11px;
    }
    table.data-table th, .stats-table th, .min-w-full th {
        background: #f3f4f6; text-transform: uppercase; letter-spacing: 0.04em; color: #374151;
    }
    table.data-table tr:nth-child(even) td, .stats-table tr:nth-child(even) td, .min-w-full tr:nth-child(even) td { background: #fafafa; }
    .table-section { background: #f3f4f6; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #374151; }
    .pill { display: inline-block; padding: 2px 6px; border-radius: 999px; border: 1px solid #e5e7eb; font-size: 10px; text-transform: uppercase; letter-spacing: 0.04em; }
    .pill.W { color: #166534; border-color: #bbf7d0; background: #ecfdf5; }
    .pill.D { color: #92400e; border-color: #fde68a; background: #fffbeb; }
    .pill.L { color: #991b1b; border-color: #fecaca; background: #fef2f2; }
    .small { font-size: 10px; color: #6b7280; }
    .rounded-xl { border-radius: 0.75rem; }
    .overflow-hidden { overflow: hidden; }
    .bg-bg-tertiary { background: #f9fafb; }
    .bg-bg-secondary { background: #eef1f7; }
    .border-b { border-bottom: 1px solid #e3e6f3; }
    .border-border-soft { border-color: #e3e6f3; }
    .transition-colors { transition: color 0.2s ease, background-color 0.2s ease; }
    .hover\:bg-bg-secondary\/60:hover { background: #f3f4f6; }
</style>
