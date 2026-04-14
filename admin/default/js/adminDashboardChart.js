(function ($) {
  "use strict";

  $(document).ready(function () {
    if (typeof Chart === "undefined") return;
    const isAdminDarkMode = document.querySelector('link[href*="night_style.css"]') !== null;
    const legendTextColor = isAdminDarkMode ? "#d1d5db" : "#444";
    const tickTextColor = isAdminDarkMode ? "#cbd5e1" : "#666";
    const gridLineColor = isAdminDarkMode ? "rgba(148, 163, 184, 0.2)" : "rgba(0, 0, 0, 0.08)";
    const ctx = document.getElementById("myChart");
    const genderCtx = document.getElementById("genderChart");
    const csrf = document.getElementById("earningsCsrf");
    const rangeButtons = document.querySelectorAll(".earnings-range");

    const context = ctx ? ctx.getContext("2d") : null;
    const safeCreateChart = function (chartContext, config) {
      if (!chartContext || typeof Chart === "undefined") {
        return null;
      }
      try {
        return new Chart(chartContext, config);
      } catch (e) {
        return null;
      }
    };

    const gradient = context.createLinearGradient(0, 0, 0, ctx.height || 300);
    gradient.addColorStop(0, "rgba(94, 53, 177, 0.5)");
    gradient.addColorStop(0.5, "rgba(94, 53, 177, 0.25)");
    gradient.addColorStop(1, "rgba(94, 53, 177, 0)");

    const gradientTwo = context.createLinearGradient(0, 0, 0, ctx.height || 300);
    gradientTwo.addColorStop(0, "rgba(246, 81, 105, 0.5)");
    gradientTwo.addColorStop(0.5, "rgba(246, 81, 105, 0.25)");
    gradientTwo.addColorStop(1, "rgba(246, 81, 105, 0)");

    const gradientThree = context.createLinearGradient(0, 0, 0, ctx.height || 300);
    gradientThree.addColorStop(0, "rgba(34, 197, 94, 0.5)");
    gradientThree.addColorStop(0.5, "rgba(34, 197, 94, 0.25)");
    gradientThree.addColorStop(1, "rgba(34, 197, 94, 0)");

    const chartConfig = {
      type: "line",
      data: {
        labels: [],
        datasets: [
          {
            label: "",
            backgroundColor: gradient,
            borderColor: "rgba(94, 53, 177, 1)",
            pointBackgroundColor: "rgba(94, 53, 177, 1)",
            fill: true,
            data: [],
            tension: 0.3
          },
          {
            label: "",
            backgroundColor: gradientTwo,
            borderColor: "rgba(246, 81, 105, 1)",
            pointBackgroundColor: "rgba(246, 81, 105, 1)",
            fill: true,
            data: [],
            tension: 0.3
          },
          {
            label: "",
            backgroundColor: gradientThree,
            borderColor: "rgba(34, 197, 94, 1)",
            pointBackgroundColor: "rgba(34, 197, 94, 1)",
            fill: true,
            data: [],
            tension: 0.3
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          tooltip: {
            mode: "index",
            intersect: false
          },
          legend: {
            labels: {
              color: legendTextColor
            }
          }
        },
        interaction: {
          mode: "nearest",
          axis: "x",
          intersect: false
        },
        scales: {
          x: {
            ticks: {
              color: tickTextColor
            },
            grid: {
              display: false
            }
          },
          y: {
            beginAtZero: true,
            ticks: {
              color: tickTextColor,
              callback: function (value) {
                return chartConfig.data.currency + value;
              }
            },
            grid: {
              color: gridLineColor,
              borderDash: [5, 5]
            }
          }
        }
      }
    };

    let earningsChart = null;
    if (context) {
      earningsChart = safeCreateChart(context, chartConfig);
    }

    function setActiveRange(range) {
      rangeButtons.forEach(btn => {
        if (btn.dataset.range === String(range)) {
          btn.classList.add("active");
        } else {
          btn.classList.remove("active");
        }
      });
    }

    function loadEarnings(range) {
      setActiveRange(range);
      if (!context) return;
      $.ajax({
        type: "POST",
        url: siteurl + "request/request.php",
        data: {
          f: "earnings_chart",
          range: range,
          csrf_token: csrf ? csrf.value : ""
        },
        success: function (resp) {
          try {
            const data = typeof resp === "string" ? JSON.parse(resp) : resp;
            if (!data || !data.labels) return;
            chartConfig.data.labels = data.labels;
            chartConfig.data.currency = data.currency || "";
            chartConfig.data.datasets[0].label = data.labelSub || "Subscriptions";
            chartConfig.data.datasets[0].data = data.subscription || [];
            chartConfig.data.datasets[1].label = data.labelPremium || "Premium";
            chartConfig.data.datasets[1].data = data.premium || [];
            chartConfig.data.datasets[2].label = data.labelBoost || "Boost";
            chartConfig.data.datasets[2].data = data.boost || [];
            earningsChart.update();
          } catch (e) {
            // ignore parse errors
          }
        }
      });
    }

    const defaultRange = 30;
    if (context) {
      loadEarnings(defaultRange);
      rangeButtons.forEach(btn => {
        btn.addEventListener("click", function () {
          const r = parseInt(this.dataset.range, 10) || defaultRange;
          loadEarnings(r);
        });
      });
    }

    if (genderCtx) {
      const genderDataEl = document.getElementById("genderData");
      if (genderDataEl) {
        try {
          const genderData = JSON.parse(genderDataEl.textContent);
          safeCreateChart(genderCtx.getContext("2d"), {
            type: "doughnut",
            data: {
              labels: genderData.labels,
              datasets: [{
                data: genderData.counts,
                backgroundColor: ["#4e6af1", "#f65169", "#f1c40f", "#6b7280"],
                borderWidth: 0
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                legend: {
                  position: "bottom",
                  labels: {
                    color: legendTextColor
                  }
                },
                tooltip: {
                  callbacks: {
                    label: function(context) {
                      const label = context.label || '';
                      const value = context.raw || 0;
                      return `${label}: ${value}`;
                    }
                  }
                }
              },
              cutout: "65%"
            }
          });
        } catch (e) {
          // ignore
        }
      }
    }

    const earningsBreakdownCtx = document.getElementById("earningsBreakdownChart");
    if (earningsBreakdownCtx) {
      const earningsBreakdownEl = document.getElementById("earningsBreakdownData");
      if (earningsBreakdownEl) {
        try {
          const breakdown = JSON.parse(earningsBreakdownEl.textContent);
          safeCreateChart(earningsBreakdownCtx.getContext("2d"), {
            type: "doughnut",
            data: {
              labels: breakdown.labels,
              datasets: [{
                data: breakdown.counts,
                backgroundColor: ["#4e6af1", "#f65169", "#22c55e"],
                borderWidth: 0
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              plugins: {
                legend: {
                  position: "bottom",
                  labels: {
                    color: legendTextColor
                  }
                },
                tooltip: {
                  callbacks: {
                    label: function(context) {
                      const label = context.label || '';
                      const value = context.raw || 0;
                      return `${label}: ${value}`;
                    }
                  }
                }
              },
              cutout: "65%"
            }
          });
        } catch (e) {
          // ignore
        }
      }
    }

    const postsChartCtx = document.getElementById("postsChart");
    const postsRangeBtns = document.querySelectorAll(".posts-range");
    if (postsChartCtx && typeof Chart !== "undefined") {
      const postsCtx = postsChartCtx.getContext("2d");
      const postsChart = safeCreateChart(postsCtx, {
        type: "bar",
        data: {
          labels: [],
          datasets: [
            { label: "Image", data: [], backgroundColor: "#4e6af1" },
            { label: "Video", data: [], backgroundColor: "#f65169" },
            { label: "Audio", data: [], backgroundColor: "#f1c40f" },
            { label: "Poll", data: [], backgroundColor: "#10b981" },
            { label: "Reels", data: [], backgroundColor: "#8b5cf6" },
            { label: "Scheduled", data: [], backgroundColor: "#6b7280" },
            { label: "Other", data: [], backgroundColor: "#111827" }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            x: {
              stacked: true,
              ticks: { color: tickTextColor },
              grid: { color: gridLineColor }
            },
            y: {
              stacked: true,
              beginAtZero: true,
              ticks: { color: tickTextColor },
              grid: { color: gridLineColor }
            }
          },
          plugins: {
            legend: {
              position: "bottom",
              labels: {
                color: legendTextColor
              }
            }
          }
        }
      });
      function setPostsRangeActive(period){
        postsRangeBtns.forEach(btn => {
          btn.classList.toggle("active", btn.dataset.range === period);
        });
      }
      function loadPostsChart(period){
        setPostsRangeActive(period);
        $.ajax({
          type: "POST",
          url: siteurl + "request/request.php",
          data: {f:'posts_chart', period: period, csrf_token: csrf ? csrf.value : ''},
          success: function(resp){
            try{
              const data = typeof resp === "string" ? JSON.parse(resp) : resp;
              postsChart.data.labels = data.labels || [];
              const series = data.series || {};
              const order = ['image','video','audio','poll','reels','scheduled','other'];
              order.forEach((key, idx) => {
                if (!postsChart.data.datasets[idx]) return;
                postsChart.data.datasets[idx].label = (data.legend && data.legend[key]) ? data.legend[key] : postsChart.data.datasets[idx].label;
                postsChart.data.datasets[idx].data = series[key] || [];
              });
              postsChart.update();
            } catch(e){}
          }
        });
      }
      const defaultPostsPeriod = "daily";
      loadPostsChart(defaultPostsPeriod);
      postsRangeBtns.forEach(btn => {
        btn.addEventListener("click", function(){
          loadPostsChart(this.dataset.range || defaultPostsPeriod);
        });
      });
    }

    // Dashboard Advanced Charts Extension: start
    const paymentMethodCtx = document.getElementById("paymentMethodChart");
    const subscriptionHealthCtx = document.getElementById("subscriptionHealthChart");
    const userConversionCtx = document.getElementById("userConversionChart");
    const revenueByTypeCtx = document.getElementById("revenueByTypeChart");
    const payoutPipelineCtx = document.getElementById("payoutPipelineChart");
    const contentMonetizationCtx = document.getElementById("contentMonetizationChart");
    const creatorFunnelCtx = document.getElementById("creatorFunnelChart");
    const contentRadarCtx = document.getElementById("contentRadarChart");

    const hasAdvancedCharts = [
      paymentMethodCtx,
      subscriptionHealthCtx,
      userConversionCtx,
      revenueByTypeCtx,
      payoutPipelineCtx,
      contentMonetizationCtx,
      creatorFunnelCtx,
      contentRadarCtx
    ].some(Boolean);

    const numberOrZero = function (value) {
      const n = Number(value);
      return Number.isFinite(n) ? n : 0;
    };

    const parseJsonSafe = function (resp) {
      if (resp && typeof resp === "object") return resp;
      if (typeof resp !== "string") return null;
      try {
        return JSON.parse(resp);
      } catch (e) {
        const start = resp.indexOf("{");
        const end = resp.lastIndexOf("}");
        if (start !== -1 && end > start) {
          try {
            return JSON.parse(resp.slice(start, end + 1));
          } catch (innerErr) {
            return null;
          }
        }
        return null;
      }
    };

    const buildAdvancedFallbackData = function () {
      const dateLabels = [];
      for (let i = 29; i >= 0; i--) {
        const d = new Date();
        d.setDate(d.getDate() - i);
        dateLabels.push(d.toLocaleDateString(undefined, { month: "short", day: "numeric" }));
      }
      const weekLabels = [];
      for (let i = 11; i >= 0; i--) {
        const d = new Date();
        d.setDate(d.getDate() - (i * 7));
        weekLabels.push(`W${String(Math.ceil((((d - new Date(d.getFullYear(), 0, 1)) / 86400000) + d.getDay() + 1) / 7)).padStart(2, "0")}`);
      }
      return {
        paymentMethods: {
          labels: ["Stripe", "PayPal", "Wallet"],
          series: { ok: [0, 0, 0], pending: [0, 0, 0], declined: [0, 0, 0] },
          legend: { ok: "OK", pending: "Pending", declined: "Declined" }
        },
        subscriptionHealth: {
          labels: weekLabels,
          active: new Array(weekLabels.length).fill(0),
          inactive: new Array(weekLabels.length).fill(0),
          declined: new Array(weekLabels.length).fill(0),
          declineRate: new Array(weekLabels.length).fill(0)
        },
        userConversion: {
          labels: dateLabels,
          registered: new Array(dateLabels.length).fill(0),
          paying: new Array(dateLabels.length).fill(0)
        },
        revenueByType: {
          labels: ["Subscription", "Post", "Boost"],
          values: [0, 0, 0],
          currency: "$"
        },
        payoutPipeline: {
          labels: ["Pending", "Paid", "Declined"],
          counts: [0, 0, 0]
        },
        contentMonetization: {
          points: [],
          currency: "$"
        },
        creatorFunnel: {
          labels: [
            "Verification submitted",
            "Verification approved",
            "Creators enabled",
            "Creators with sales",
            "Creators with subscribers"
          ],
          values: [0, 0, 0, 0, 0]
        },
        contentRadar: {
          labels: ["Image", "Video", "Audio", "Poll", "Reels"],
          postShare: [0, 0, 0, 0, 0],
          revenueShare: [0, 0, 0, 0, 0]
        },
        datasets: {
          active: "Active",
          inactive: "Inactive",
          declined: "Declined",
          declineRate: "Decline rate",
          registeredUsers: "Registered users",
          payingUsers: "Paying users",
          postShare: "Post share %",
          revenueShare: "Revenue share %"
        },
        labelsMeta: {
          postsAxis: "Posts",
          revenueAxis: "Revenue",
          paymentsSuffix: "payments"
        }
      };
    };

    const renderAdvancedCharts = function (payload) {
      const data = payload || buildAdvancedFallbackData();
      if (!data || typeof data !== "object") return;

      const datasets = data.datasets || {};
      const pm = data.paymentMethods || {};
      const sh = data.subscriptionHealth || {};
      const uc = data.userConversion || {};
      const rt = data.revenueByType || {};
      const pp = data.payoutPipeline || {};
      const cm = data.contentMonetization || {};
      const cf = data.creatorFunnel || {};
      const cr = data.contentRadar || {};
      const labelsMeta = data.labelsMeta || {};

      if (paymentMethodCtx) {
        safeCreateChart(paymentMethodCtx.getContext("2d"), {
          type: "bar",
          data: {
            labels: pm.labels || [],
            datasets: [
              {
                label: (pm.legend && pm.legend.ok) ? pm.legend.ok : "OK",
                data: (pm.series && pm.series.ok) ? pm.series.ok : [],
                backgroundColor: "#10b981"
              },
              {
                label: (pm.legend && pm.legend.pending) ? pm.legend.pending : "Pending",
                data: (pm.series && pm.series.pending) ? pm.series.pending : [],
                backgroundColor: "#f59e0b"
              },
              {
                label: (pm.legend && pm.legend.declined) ? pm.legend.declined : "Declined",
                data: (pm.series && pm.series.declined) ? pm.series.declined : [],
                backgroundColor: "#ef4444"
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
              x: {
                stacked: true,
                ticks: { color: tickTextColor },
                grid: { color: gridLineColor }
              },
              y: {
                stacked: true,
                beginAtZero: true,
                ticks: { color: tickTextColor },
                grid: { color: gridLineColor }
              }
            },
            plugins: {
              legend: {
                labels: { color: legendTextColor }
              }
            }
          }
        });
      }

      if (subscriptionHealthCtx) {
        const shLabels = Array.isArray(sh.labels) && sh.labels.length
          ? sh.labels
          : ["W01", "W02", "W03", "W04", "W05", "W06", "W07", "W08", "W09", "W10", "W11", "W12"];
        const shActive = Array.isArray(sh.active) && sh.active.length ? sh.active : new Array(shLabels.length).fill(0);
        const shInactive = Array.isArray(sh.inactive) && sh.inactive.length ? sh.inactive : new Array(shLabels.length).fill(0);
        const shDeclined = Array.isArray(sh.declined) && sh.declined.length ? sh.declined : new Array(shLabels.length).fill(0);
        const shDeclineRate = Array.isArray(sh.declineRate) && sh.declineRate.length ? sh.declineRate : new Array(shLabels.length).fill(0);

        const subscriptionChart = safeCreateChart(subscriptionHealthCtx.getContext("2d"), {
          type: "bar",
          data: {
            labels: shLabels,
            datasets: [
              {
                type: "bar",
                label: datasets.active || "Active",
                data: shActive,
                backgroundColor: "rgba(34, 197, 94, 0.8)"
              },
              {
                type: "bar",
                label: datasets.inactive || "Inactive",
                data: shInactive,
                backgroundColor: "rgba(59, 130, 246, 0.75)"
              },
              {
                type: "bar",
                label: datasets.declined || "Declined",
                data: shDeclined,
                backgroundColor: "rgba(239, 68, 68, 0.75)"
              },
              {
                type: "line",
                label: datasets.declineRate || "Decline rate",
                data: shDeclineRate,
                borderColor: "#f59e0b",
                backgroundColor: "rgba(245, 158, 11, 0.25)",
                fill: false,
                yAxisID: "y1",
                tension: 0.35
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
              x: {
                ticks: { color: tickTextColor },
                grid: { color: gridLineColor }
              },
              y: {
                beginAtZero: true,
                ticks: { color: tickTextColor },
                grid: { color: gridLineColor }
              },
              y1: {
                beginAtZero: true,
                position: "right",
                ticks: {
                  color: tickTextColor,
                  callback: function (value) {
                    return `${numberOrZero(value)}%`;
                  }
                },
                grid: { drawOnChartArea: false }
              }
            },
            plugins: {
              legend: {
                labels: { color: legendTextColor }
              }
            }
          }
        });
        if (!subscriptionChart) {
          safeCreateChart(subscriptionHealthCtx.getContext("2d"), {
            type: "bar",
            data: {
              labels: shLabels,
              datasets: [
                {
                  label: datasets.active || "Active",
                  data: shActive,
                  backgroundColor: "rgba(34, 197, 94, 0.6)"
                },
                {
                  label: datasets.inactive || "Inactive",
                  data: shInactive,
                  backgroundColor: "rgba(59, 130, 246, 0.55)"
                },
                {
                  label: datasets.declined || "Declined",
                  data: shDeclined,
                  backgroundColor: "rgba(239, 68, 68, 0.55)"
                }
              ]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              legend: {
                display: true
              }
            }
          });
        }
      }

      if (userConversionCtx) {
        safeCreateChart(userConversionCtx.getContext("2d"), {
          type: "line",
          data: {
            labels: uc.labels || [],
            datasets: [
              {
                label: datasets.registeredUsers || "Registered users",
                data: uc.registered || [],
                borderColor: "#4e6af1",
                backgroundColor: "rgba(78, 106, 241, 0.18)",
                fill: true,
                tension: 0.32
              },
              {
                label: datasets.payingUsers || "Paying users",
                data: uc.paying || [],
                borderColor: "#f65169",
                backgroundColor: "rgba(246, 81, 105, 0.18)",
                fill: true,
                tension: 0.32
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
              x: {
                ticks: { color: tickTextColor },
                grid: { display: false }
              },
              y: {
                beginAtZero: true,
                ticks: { color: tickTextColor },
                grid: { color: gridLineColor }
              }
            },
            plugins: {
              legend: {
                labels: { color: legendTextColor }
              }
            }
          }
        });
      }

      if (revenueByTypeCtx) {
        const currency = rt.currency || "";
        safeCreateChart(revenueByTypeCtx.getContext("2d"), {
          type: "bar",
          data: {
            labels: rt.labels || [],
            datasets: [
              {
                label: "",
                data: rt.values || [],
                backgroundColor: "rgba(99, 102, 241, 0.8)",
                borderRadius: 8
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
              x: {
                ticks: { color: tickTextColor },
                grid: { color: gridLineColor }
              },
              y: {
                beginAtZero: true,
                ticks: {
                  color: tickTextColor,
                  callback: function (value) {
                    return currencyFormatter(currency, value);
                  }
                },
                grid: { color: gridLineColor }
              }
            },
            plugins: {
              legend: { display: false },
              tooltip: {
                callbacks: {
                  label: function (context) {
                    return currencyFormatter(currency, context.raw);
                  }
                }
              }
            }
          }
        });
      }

      if (payoutPipelineCtx) {
        const payoutLabels = Array.isArray(pp.labels) && pp.labels.length ? pp.labels : ["Pending", "Paid", "Declined"];
        const payoutRaw = Array.isArray(pp.counts) && pp.counts.length ? pp.counts : [0, 0, 0];
        const payoutCounts = payoutLabels.map(function (_, idx) {
          return numberOrZero(payoutRaw[idx]);
        });
        const hasPayoutData = payoutCounts.some(function (value) { return value > 0; });

        if (hasPayoutData) {
          safeCreateChart(payoutPipelineCtx.getContext("2d"), {
            type: "doughnut",
            data: {
              labels: payoutLabels,
              datasets: [
                {
                  data: payoutCounts,
                  backgroundColor: ["#f59e0b", "#10b981", "#ef4444"],
                  borderWidth: 0
                }
              ]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              cutout: "64%",
              plugins: {
                legend: {
                  position: "bottom",
                  labels: { color: legendTextColor }
                }
              }
            }
          });
        } else {
          safeCreateChart(payoutPipelineCtx.getContext("2d"), {
            type: "bar",
            data: {
              labels: payoutLabels,
              datasets: [
                {
                  label: "",
                  data: payoutCounts,
                  backgroundColor: ["rgba(245, 158, 11, 0.7)", "rgba(16, 185, 129, 0.7)", "rgba(239, 68, 68, 0.7)"],
                  borderRadius: 8
                }
              ]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              scales: {
                x: {
                  ticks: { color: tickTextColor },
                  grid: { color: gridLineColor }
                },
                y: {
                  beginAtZero: true,
                  ticks: { color: tickTextColor },
                  grid: { color: gridLineColor }
                }
              },
              plugins: {
                legend: { display: false }
              }
            }
          });
        }
      }

      if (contentMonetizationCtx) {
        const currency = cm.currency || "";
        const points = Array.isArray(cm.points) ? cm.points : [];
        safeCreateChart(contentMonetizationCtx.getContext("2d"), {
          type: "bubble",
          data: {
            datasets: [
              {
                label: "",
                data: points.map(function (point) {
                  return {
                    x: numberOrZero(point.x),
                    y: numberOrZero(point.y),
                    r: numberOrZero(point.r)
                  };
                }),
                backgroundColor: [
                  "rgba(78, 106, 241, 0.55)",
                  "rgba(246, 81, 105, 0.55)",
                  "rgba(245, 158, 11, 0.55)",
                  "rgba(16, 185, 129, 0.55)",
                  "rgba(139, 92, 246, 0.55)"
                ],
                borderColor: ["#4e6af1", "#f65169", "#f59e0b", "#10b981", "#8b5cf6"],
                borderWidth: 1.4
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
              x: {
                beginAtZero: true,
                ticks: { color: tickTextColor },
                title: {
                  display: true,
                  text: labelsMeta.postsAxis || "Posts",
                  color: tickTextColor
                },
                grid: { color: gridLineColor }
              },
              y: {
                beginAtZero: true,
                ticks: {
                  color: tickTextColor,
                  callback: function (value) {
                    return currencyFormatter(currency, value);
                  }
                },
                title: {
                  display: true,
                  text: labelsMeta.revenueAxis || "Revenue",
                  color: tickTextColor
                },
                grid: { color: gridLineColor }
              }
            },
            plugins: {
              legend: { display: false },
              tooltip: {
                callbacks: {
                  label: function (context) {
                    const point = points[context.dataIndex] || {};
                    const label = point.label || "";
                    return `${label}: ${currencyFormatter(currency, context.raw.y)} / ${numberOrZero(point.payments)} ${(labelsMeta.paymentsSuffix || "payments")}`;
                  }
                }
              }
            }
          }
        });
      }

      if (creatorFunnelCtx) {
        safeCreateChart(creatorFunnelCtx.getContext("2d"), {
          type: "bar",
          data: {
            labels: cf.labels || [],
            datasets: [
              {
                label: "",
                data: cf.values || [],
                backgroundColor: [
                  "rgba(99, 102, 241, 0.8)",
                  "rgba(59, 130, 246, 0.8)",
                  "rgba(16, 185, 129, 0.8)",
                  "rgba(245, 158, 11, 0.8)",
                  "rgba(244, 63, 94, 0.8)"
                ],
                borderRadius: 8
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
              x: {
                ticks: { color: tickTextColor },
                grid: { display: false }
              },
              y: {
                beginAtZero: true,
                ticks: { color: tickTextColor },
                grid: { color: gridLineColor }
              }
            },
            plugins: {
              legend: { display: false }
            }
          }
        });
      }

      if (contentRadarCtx) {
        safeCreateChart(contentRadarCtx.getContext("2d"), {
          type: "radar",
          data: {
            labels: cr.labels || [],
            datasets: [
              {
                label: datasets.postShare || "Post share %",
                data: cr.postShare || [],
                borderColor: "#4e6af1",
                backgroundColor: "rgba(78, 106, 241, 0.22)"
              },
              {
                label: datasets.revenueShare || "Revenue share %",
                data: cr.revenueShare || [],
                borderColor: "#f65169",
                backgroundColor: "rgba(246, 81, 105, 0.2)"
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
              r: {
                beginAtZero: true,
                grid: { color: gridLineColor },
                angleLines: { color: gridLineColor },
                pointLabels: { color: tickTextColor },
                ticks: {
                  color: tickTextColor,
                  backdropColor: "transparent",
                  callback: function (value) {
                    return `${numberOrZero(value)}%`;
                  }
                }
              }
            },
            plugins: {
              legend: {
                labels: { color: legendTextColor }
              }
            }
          }
        });
      }
    };

    const currencyFormatter = function (currency, value) {
      return `${currency || ""}${numberOrZero(value)}`;
    };

    if (hasAdvancedCharts) {
      $.ajax({
        type: "POST",
        url: siteurl + "request/request.php",
        timeout: 5000,
        data: {
          f: "dashboard_advanced_charts",
          csrf_token: csrf ? csrf.value : ""
        },
        success: function (resp) {
          const data = parseJsonSafe(resp);
          renderAdvancedCharts(data);
        },
        error: function () {
          renderAdvancedCharts(null);
        }
      });
    }
    // Dashboard Advanced Charts Extension: end
  });
})(jQuery);
