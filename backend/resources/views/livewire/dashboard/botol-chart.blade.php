<div class="card">
    <div class="card-body">
        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Botol Terkumpul ? 7 Hari Terakhir</h3>
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

    const isDark = document.documentElement.classList.contains("dark");
    const textColor  = isDark ? "#9ca3af" : "#6b7280";
    const gridColor  = isDark ? "#374151" : "#f3f4f6";
    const bgColor    = isDark ? "#1f2937" : "#ffffff";

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
        colors: ["#16a34a"],
        dataLabels: { enabled: false },
        grid: { borderColor: gridColor },
        plotOptions: { bar: { borderRadius: 5 } },
        tooltip: {
            theme: isDark ? "dark" : "light",
        },
    });
    chart.render();

    // Update chart theme when dark mode toggles
    document.documentElement.addEventListener("classchange", () => {
        const d = document.documentElement.classList.contains("dark");
        chart.updateOptions({
            tooltip: { theme: d ? "dark" : "light" },
            grid: { borderColor: d ? "#374151" : "#f3f4f6" },
            xaxis: { labels: { style: { colors: d ? "#9ca3af" : "#6b7280" } } },
        });
    });
})();
</script>
@endpush
