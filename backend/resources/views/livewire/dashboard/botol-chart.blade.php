<div class="card">
    <div class="card-body">
        <h3 class="text-sm font-semibold text-gray-700  mb-4">Botol Terkumpul ? 7 Hari Terakhir</h3>
        <div id="botol-chart" class="h-56" wire:ignore></div>
    </div>
</div>

@push("scripts")
<script>
(function initBotolChart() {
    const labels = @json($chartData["labels"] ?? []);
    const values = @json($chartData["values"] ?? []);

    if (typeof ApexCharts === "undefined") {
        setTimeout(initBotolChart, 200);
        return;
    }

    
    const textColor  = "#6b7280";
    const gridColor  = "#f3f4f6";
    

    const chart = new ApexCharts(document.getElementById("botol-chart"), {
        chart: {
            type: "bar",
            height: 200,
            toolbar: { show: false },
            animations: { enabled: true },
            background: "transparent",
        },
        series: [{ name: "Botol", data: values }],
        xaxis: {
            categories: labels,
            labels: { style: { fontSize: "11px", colors: textColor } }
        },
        yaxis: {
            labels: { style: { fontSize: "11px", colors: textColor } }
        },
        colors: ["#059669"],
        dataLabels: { enabled: false },
        grid: { borderColor: gridColor },
        plotOptions: { bar: { borderRadius: 5 } },
        tooltip: {
            theme: "light",
        },
    });
    chart.render();

    
})();
</script>
@endpush


