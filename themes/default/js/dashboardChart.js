(function () {
  "use strict";

  document.addEventListener("DOMContentLoaded", function () {
    if (typeof Chart === "undefined") return;
    const canvas = document.getElementById("myChart");
    if (!canvas) return;
 
    const chartDataScript = document.getElementById("chartData");
    if (!chartDataScript) return;

    let chartData;
    try {
      chartData = JSON.parse(chartDataScript.textContent);
    } catch (e) { 
      return;
    }

    const ctx = canvas.getContext("2d");
    const normalizeSeries = function (series) {
      if (!Array.isArray(series)) {
        return [];
      }
      return series.map(function (value) {
        const parsed = Number(value);
        return Number.isFinite(parsed) ? parsed : 0;
      });
    };

    const chart = new Chart(ctx, {
      type: "line",
      data: {
        labels: chartData.labels || [],
        datasets: [
          {
            label: chartData.labelSub || "Subscriptions",
            backgroundColor: "rgba(250, 180, 41, 0.1)",
            borderColor: "rgb(250, 180, 41)",
            pointBackgroundColor: "rgb(250, 180, 41)",
            pointRadius: 4,
            pointHoverRadius: 6,
            fill: true,
            data: normalizeSeries(chartData.subscription)
          },
          {
            label: chartData.labelPoint || "Points",
            backgroundColor: "rgba(255, 99, 132, 0.1)",
            borderColor: "rgb(255, 99, 132)",
            pointBackgroundColor: "rgb(255, 99, 132)",
            pointRadius: 4,
            pointHoverRadius: 6,
            fill: true,
            data: normalizeSeries(chartData.pointEarnings)
          },
          {
            label: chartData.labelProduct || "Products",
            backgroundColor: "rgba(93, 81, 246, 0.1)",
            borderColor: "rgb(93, 81, 246)",
            pointBackgroundColor: "rgb(93, 81, 246)",
            pointRadius: 4,
            pointHoverRadius: 6,
            fill: true,
            data: normalizeSeries(chartData.productEarnings)
          },
          {
            label: chartData.labelCampaign || "Campaign",
            backgroundColor: "rgba(14, 165, 233, 0.1)",
            borderColor: "rgb(14, 165, 233)",
            pointBackgroundColor: "rgb(14, 165, 233)",
            pointRadius: 4,
            pointHoverRadius: 6,
            fill: true,
            data: normalizeSeries(chartData.campaignEarnings)
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
          mode: "nearest",
          intersect: true
        },
        plugins: {
          tooltip: {
            mode: "index",
            intersect: false
          }
        },
        layout: {
          padding: {
            top: 20,
            left: 15,
            right: 15,
            bottom: 20
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: function (value) {
                return (chartData.currency || "") + value;
              }
            }
          }
        }
      }
    });

    // Resize observer for responsiveness
    if ('ResizeObserver' in window) {
      const resizeObserver = new ResizeObserver(() => {
        chart.resize();
      });
      resizeObserver.observe(canvas.parentElement);
    }
  });
})();
