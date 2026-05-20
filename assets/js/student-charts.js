/**
 * student-charts.js - Student Dashboard Analytics Module
 * Hardcoded with BLACK lines for maximum visibility
 */

// VERY DARK and VISIBLE colors
const STUDENT_CHART_COLORS = {
  // Line chart - using BLACK for maximum visibility
  lineColor: "#000000",
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

  // Performance-based colors for marks
  excellent: "#1B5E20",
  good: "#0D47A1",
  average: "#E65100",
  poor: "#B71C1C",

  // Grid
  gridColor: "#CCCCCC",
  textColor: "#000000",
  axisColor: "#333333",
};

let activeStudentChart = null;

function getStudentTrendData(trend, dbData) {
  switch (trend) {
    case "marks":
      return {
        labels: dbData.subjectNames,
        datasets: [
          {
            label: "My Marks",
            data: dbData.studentMarks,
            backgroundColor: STUDENT_CHART_COLORS.barColors.slice(
              0,
              dbData.subjectNames.length,
            ),
            borderColor: "#ffffff",
            borderWidth: 2,
            borderRadius: 8,
            barPercentage: 0.7,
          },
        ],
        note: "📊 Your marks across different subjects",
        yAxisLabel: "Marks (out of 100)",
        dataType: "marks",
        yMax: 100,
      };

    case "attendance":
      return {
        labels: dbData.attendanceMonths,
        datasets: [
          {
            label: "Attendance Rate (%)",
            data: dbData.attendanceRates,
            backgroundColor: STUDENT_CHART_COLORS.lineFill,
            borderColor: STUDENT_CHART_COLORS.lineColor,
            borderWidth: 4,
            fill: true,
            pointBackgroundColor: STUDENT_CHART_COLORS.lineColor,
            pointBorderColor: "#ffffff",
            pointBorderWidth: 3,
            pointRadius: 7,
            pointHoverRadius: 10,
            tension: 0.2,
          },
        ],
        note: "📋 Your attendance trend over the last 6 months",
        yAxisLabel: "Attendance Percentage (%)",
        dataType: "percentage",
        yMax: 100,
      };

    case "passfail":
      return {
        labels: ["Passed", "Failed"],
        datasets: [
          {
            label: "Subject Count",
            data: [dbData.passedCount, dbData.failedCount],
            backgroundColor: STUDENT_CHART_COLORS.doughnutPassFail,
            borderColor: "#ffffff",
            borderWidth: 3,
            hoverOffset: 15,
          },
        ],
        note: `🎯 Your performance: ${dbData.passedCount} passed, ${dbData.failedCount} failed subjects`,
        yAxisLabel: "Number of Subjects",
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

function updateStudentChart(chartType, trend, dbData) {
  const canvas = document.getElementById("analyticsChart");
  if (!canvas) return;

  const ctx = canvas.getContext("2d");
  const trendData = getStudentTrendData(trend, dbData);

  if (activeStudentChart) {
    activeStudentChart.destroy();
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
      bgColors = STUDENT_CHART_COLORS.doughnutPassFail;
    } else if (trend === "marks") {
      bgColors = STUDENT_CHART_COLORS.barColors.slice(
        0,
        trendData.labels.length,
      );
    } else {
      bgColors = STUDENT_CHART_COLORS.doughnutDefault.slice(
        0,
        trendData.labels.length,
      );
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
    if (trend === "marks") {
      // Color bars based on performance
      bgColors = trendData.datasets[0].data.map((score) => {
        if (score >= 80) return STUDENT_CHART_COLORS.excellent;
        if (score >= 60) return STUDENT_CHART_COLORS.good;
        if (score >= 40) return STUDENT_CHART_COLORS.average;
        return STUDENT_CHART_COLORS.poor;
      });
    } else if (trend === "passfail") {
      bgColors = STUDENT_CHART_COLORS.doughnutPassFail;
    } else {
      bgColors = STUDENT_CHART_COLORS.barColors[0];
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
              if (trend === "marks") {
                if (value >= 80) return `🏆 Excellent: ${value}/100`;
                if (value >= 60) return `👍 Good: ${value}/100`;
                if (value >= 40) return `📚 Average: ${value}/100`;
                return `⚠️ Needs Improvement: ${value}/100`;
              }
              if (trend === "attendance") {
                if (value >= 85) return `🌟 Excellent Attendance: ${value}%`;
                if (value >= 75) return `👍 Good Attendance: ${value}%`;
                if (value >= 60) return `📚 Average Attendance: ${value}%`;
                return `⚠️ Low Attendance: ${value}%`;
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
                if (trendData.dataType === "percentage" || trend === "marks") {
                  return value + "%";
                }
                return value;
              },
            },
          },
          x: {
            title: {
              display: true,
              text: trend === "attendance" ? "Months" : "Subjects",
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

  activeStudentChart = new Chart(ctx, chartConfig);
}

async function downloadStudentAnalyticsChart() {
  const container = document.querySelector(".dashboard-analytics");
  if (!container) return;

  const btn = document.querySelector(".download-analytics-btn");
  const originalText = btn ? btn.innerHTML : "Download";

  if (btn) {
    btn.innerHTML = "⏳ Capturing...";
    btn.disabled = true;
  }

  try {
    const capturedCanvas = await html2canvas(container, {
      scale: 2.5,
      backgroundColor: "#ffffff",
      useCORS: true,
    });

    const link = document.createElement("a");
    const timestamp = new Date().toISOString().slice(0, 19).replace(/:/g, "-");
    link.download = `Student_Dashboard_Chart_${timestamp}.png`;
    link.href = capturedCanvas.toDataURL("image/png");
    link.click();

    if (btn) {
      btn.innerHTML = "✓ Downloaded!";
      setTimeout(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
      }, 2000);
    }
  } catch (error) {
    console.error("Download failed:", error);
    if (btn) {
      btn.innerHTML = "❌ Failed";
      alert("Failed to download chart");
      setTimeout(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
      }, 2000);
    }
  }
}

function initStudentDashboardAnalytics(dbData) {
  const trendSelect = document.getElementById("trendSelect");
  const chartTypeSelect = document.getElementById("chartTypeSelect");
  const container = document.querySelector(".dashboard-analytics");
  const downloadBtn = document.querySelector(".download-analytics-btn");

  let currentTrend = "marks";
  let currentChartType = "bar";

  updateStudentChart(currentChartType, currentTrend, dbData);

  if (trendSelect) {
    trendSelect.addEventListener("change", function (e) {
      currentTrend = e.target.value;
      updateStudentChart(currentChartType, currentTrend, dbData);
    });
  }

  if (chartTypeSelect) {
    chartTypeSelect.addEventListener("change", function (e) {
      currentChartType = e.target.value;
      updateStudentChart(currentChartType, currentTrend, dbData);
    });
  }

  if (downloadBtn && container) {
    downloadBtn.onclick = () => downloadStudentAnalyticsChart();
  }
}
