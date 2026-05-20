/**
 * charts.js - Dashboard Analytics Charts Module
 */

// VERY DARK and VISIBLE colors
const CHART_COLORS = {
  // Line chart - using BLACK for maximum visibility
  lineColor: "#000000", // BLACK line - most visible
  lineFill: "rgba(0, 0, 0, 0.05)",

  // Bar chart colors - DARK and DISTINCT
  barColors: [
    "#1B5E20",
    "#0D47A1",
    "#E65100",
    "#4A148C",
    "#004D40",
    "#B71C1C",
    "#F57F17",
    "#3E2723",
  ],

  // Doughnut colors
  doughnutPassFail: ["#2E7D32", "#D32F2F"],
  doughnutDefault: [
    "#1B5E20",
    "#0D47A1",
    "#E65100",
    "#4A148C",
    "#004D40",
    "#B71C1C",
  ],

  // Grid
  gridColor: "#CCCCCC",
  textColor: "#000000",
  axisColor: "#333333",
};

let activeChart = null;

function getTrendData(trend, dbData) {
  switch (trend) {
    case "attendance":
      return {
        labels: dbData.attendanceMonths,
        datasets: [
          {
            label: "Attendance Rate (%)",
            data: dbData.attendanceRates,
            backgroundColor: CHART_COLORS.lineFill,
            borderColor: CHART_COLORS.lineColor,
            borderWidth: 4,
            fill: true,
            pointBackgroundColor: CHART_COLORS.lineColor,
            pointBorderColor: "#ffffff",
            pointBorderWidth: 3,
            pointRadius: 7,
            pointHoverRadius: 10,
            tension: 0.2,
          },
        ],
        note: "📋 Student attendance trend over the last 6 months",
        yAxisLabel: "Attendance Percentage (%)",
        dataType: "percentage",
        yMax: 100,
      };

    case "passfail":
      return {
        labels: ["Passed", "Failed"],
        datasets: [
          {
            label: "Student Count",
            data: [dbData.passedCount, dbData.failedCount],
            backgroundColor: CHART_COLORS.doughnutPassFail,
            borderColor: "#ffffff",
            borderWidth: 3,
            hoverOffset: 15,
          },
        ],
        note: `🎯 Overall student performance: ${dbData.passedCount} passed, ${dbData.failedCount} failed`,
        yAxisLabel: "Number of Students",
        dataType: "count",
        yMax: null,
      };

    case "department":
      return {
        labels: dbData.departments,
        datasets: [
          {
            label: "Enrolled Students",
            data: dbData.departmentCounts,
            backgroundColor: CHART_COLORS.barColors.slice(
              0,
              dbData.departments.length,
            ),
            borderColor: "#ffffff",
            borderWidth: 2,
            borderRadius: 8,
            barPercentage: 0.7,
          },
        ],
        note: "👥 Student distribution across different departments",
        yAxisLabel: "Number of Students",
        dataType: "count",
        yMax: null,
      };

    default:
      return {
        labels: [],
        datasets: [],
        note: "No data available",
        yAxisLabel: "",
        dataType: "",
        yMax: null,
      };
  }
}

function updateChart(chartType, trend, dbData) {
  const canvas = document.getElementById("analyticsChart");
  if (!canvas) return;

  const ctx = canvas.getContext("2d");
  const trendData = getTrendData(trend, dbData);

  if (activeChart) {
    activeChart.destroy();
  }

  const noteElement = document.getElementById("chartNote");
  if (noteElement) {
    noteElement.innerHTML = `<span class="note-badge">ℹ️</span> ${trendData.note}`;
  }

  if (!trendData.labels.length || !trendData.datasets[0].data.length) {
    ctx.font = "16px Segoe UI";
    ctx.fillStyle = "#666";
    ctx.textAlign = "center";
    ctx.fillText("No data available", canvas.width / 2, canvas.height / 2);
    return;
  }

  let chartData = {
    labels: trendData.labels,
    datasets: [],
  };

  if (chartType === "doughnut") {
    // For doughnut - use distinct colors
    let bgColors;
    if (trend === "passfail") {
      bgColors = CHART_COLORS.doughnutPassFail;
    } else {
      bgColors = CHART_COLORS.doughnutDefault.slice(0, trendData.labels.length);
    }
    chartData.datasets = [
      {
        label: trendData.datasets[0].label,
        data: trendData.datasets[0].data,
        backgroundColor: bgColors,
        borderColor: "#ffffff",
        borderWidth: 3,
        hoverOffset: 10,
      },
    ];
  } else if (chartType === "bar") {
    let bgColors;
    if (trend === "department") {
      bgColors = CHART_COLORS.barColors.slice(0, trendData.labels.length);
    } else if (trend === "passfail") {
      bgColors = CHART_COLORS.doughnutPassFail;
    } else {
      bgColors = CHART_COLORS.barColors[0];
    }
    chartData.datasets = [
      {
        label: trendData.datasets[0].label,
        data: trendData.datasets[0].data,
        backgroundColor: bgColors,
        borderColor: "#ffffff",
        borderWidth: 2,
        borderRadius: 8,
        barPercentage: 0.7,
      },
    ];
  } else {
    // LINE CHART - using BLACK line for maximum visibility
    chartData.datasets = [
      {
        label: trendData.datasets[0].label,
        data: trendData.datasets[0].data,
        backgroundColor: "rgba(0, 0, 0, 0.05)",
        borderColor: "#000000",
        borderWidth: 4,
        fill: true,
        pointBackgroundColor: "#000000",
        pointBorderColor: "#ffffff",
        pointBorderWidth: 3,
        pointRadius: 7,
        pointHoverRadius: 10,
        tension: 0.2,
      },
    ];
  }

  let chartConfig = {
    type: chartType,
    data: chartData,
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: chartType === "doughnut" ? "bottom" : "top",
          labels: {
            font: { size: 12, weight: "bold" },
            color: "#000000",
            usePointStyle: true,
            boxWidth: 12,
            padding: 15,
          },
        },
        tooltip: {
          backgroundColor: "#000000",
          titleColor: "#ffffff",
          bodyColor: "#dddddd",
          borderColor: "#000000",
          borderWidth: 1,
          cornerRadius: 8,
          callbacks: {
            label: function (context) {
              let value = context.raw;
              if (trendData.dataType === "percentage") {
                return `${value}%`;
              }
              if (trend === "passfail" && chartType === "doughnut") {
                const total = dbData.passedCount + dbData.failedCount;
                const percent = ((value / total) * 100).toFixed(1);
                return `${context.label}: ${value} (${percent}%)`;
              }
              return `${value}`;
            },
          },
        },
      },
      ...(chartType !== "doughnut" && {
        scales: {
          y: {
            beginAtZero: true,
            ...(trendData.yMax === 100 && { max: 100 }),
            title: {
              display: true,
              text: trendData.yAxisLabel,
              font: { weight: "bold", size: 12 },
              color: "#000000",
            },
            grid: { color: "#CCCCCC", drawBorder: true },
            ticks: {
              color: "#333333",
              stepSize: trendData.yMax === 100 ? 20 : undefined,
              callback: function (value) {
                if (trendData.dataType === "percentage") {
                  return value + "%";
                }
                return value;
              },
            },
          },
          x: {
            title: {
              display: true,
              text: trend === "attendance" ? "Months" : "Categories",
              font: { weight: "bold", size: 12 },
              color: "#000000",
            },
            grid: { display: false },
            ticks: { color: "#333333", fontWeight: "bold" },
          },
        },
      }),
      ...(chartType === "doughnut" && {
        cutout: "55%",
        radius: "75%",
      }),
      animation: { duration: 800 },
    },
  };

  activeChart = new Chart(ctx, chartConfig);
}

async function downloadAnalyticsChart(container) {
  const btn = document.querySelector(".download-analytics-btn");
  if (!btn) return;

  const originalText = btn.innerHTML;
  btn.innerHTML = "⏳ Capturing...";
  btn.disabled = true;

  try {
    const capturedCanvas = await html2canvas(container, {
      scale: 2.5,
      backgroundColor: "#ffffff",
      useCORS: true,
    });

    const link = document.createElement("a");
    const timestamp = new Date().toISOString().slice(0, 19).replace(/:/g, "-");
    link.download = `Dashboard_Chart_${timestamp}.png`;
    link.href = capturedCanvas.toDataURL("image/png");
    link.click();

    btn.innerHTML = "✓ Downloaded!";
    setTimeout(() => {
      btn.innerHTML = originalText;
      btn.disabled = false;
    }, 2000);
  } catch (error) {
    console.error("Download failed:", error);
    btn.innerHTML = "❌ Failed";
    alert("Failed to download chart");
    setTimeout(() => {
      btn.innerHTML = originalText;
      btn.disabled = false;
    }, 2000);
  }
}

function initDashboardAnalytics(dbData) {
  const trendSelect = document.getElementById("trendSelect");
  const chartTypeSelect = document.getElementById("chartTypeSelect");
  const container = document.querySelector(".dashboard-analytics");
  const downloadBtn = document.querySelector(".download-analytics-btn");

  let currentTrend = "attendance";
  let currentChartType = "line";

  updateChart(currentChartType, currentTrend, dbData);

  if (trendSelect) {
    trendSelect.addEventListener("change", function (e) {
      currentTrend = e.target.value;
      updateChart(currentChartType, currentTrend, dbData);
    });
  }

  if (chartTypeSelect) {
    chartTypeSelect.addEventListener("change", function (e) {
      currentChartType = e.target.value;
      updateChart(currentChartType, currentTrend, dbData);
    });
  }

  if (downloadBtn && container) {
    downloadBtn.onclick = () => downloadAnalyticsChart(container);
  }
}
