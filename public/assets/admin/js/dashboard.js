(function () {
    "use strict";

    var payloadElement = document.getElementById("dashboard-chart-data");
    var dashboardRoot = document.querySelector(".admin-dashboard");

    if (!payloadElement || !dashboardRoot || typeof Chart === "undefined") {
        return;
    }

    var charts;

    try {
        charts = JSON.parse(payloadElement.textContent || "{}");
    } catch (error) {
        return;
    }

    var labels = window.dashboardChartLabels || {};
    var isRtl = (document.documentElement.getAttribute("dir") || "").toLowerCase() === "rtl";
    var reducedMotionQuery = window.matchMedia ? window.matchMedia("(prefers-reduced-motion: reduce)") : null;
    var prefersReducedMotion = reducedMotionQuery ? reducedMotionQuery.matches : false;
    var locale = document.documentElement.getAttribute("lang") || "en";

    function getCssVariable(name, fallback) {
        var value = window.getComputedStyle(dashboardRoot).getPropertyValue(name);
        return value ? value.trim() : fallback;
    }

    function formatInteger(value) {
        var numericValue = Number(value) || 0;

        if (typeof Intl !== "undefined" && Intl.NumberFormat) {
            return new Intl.NumberFormat(locale, { maximumFractionDigits: 0 }).format(numericValue);
        }

        return String(Math.round(numericValue));
    }

    function createGradient(context, canvas, startColor, endColor) {
        var height = canvas && canvas.height ? canvas.height : 220;
        var gradient = context.createLinearGradient(0, 0, 0, height);
        gradient.addColorStop(0, startColor);
        gradient.addColorStop(1, endColor);
        return gradient;
    }

    function getDatasetPalette(context, canvas, key) {
        if (key === "customers") {
            return {
                line: "#8b5cf6",
                fill: createGradient(context, canvas, "rgba(139, 92, 246, 0.24)", "rgba(139, 92, 246, 0.02)")
            };
        }

        return {
            line: "#4f46e5",
            fill: createGradient(context, canvas, "rgba(79, 70, 229, 0.24)", "rgba(79, 70, 229, 0.02)")
        };
    }

    function getChartTheme() {
        return {
            muted: getCssVariable("--dashboard-muted", "#6b7280"),
            grid: getCssVariable("--dashboard-chart-grid", "rgba(148, 163, 184, 0.14)"),
            tooltipBg: getCssVariable("--dashboard-chart-tooltip", "rgba(15, 23, 42, 0.92)"),
            tooltipText: getCssVariable("--dashboard-chart-tooltip-text", "#f8fafc"),
            pointStroke: getCssVariable("--dashboard-surface", "#ffffff")
        };
    }

    function formatPercentage(value) {
        var numericValue = Number(value) || 0;

        if (typeof Intl !== "undefined" && Intl.NumberFormat) {
            return new Intl.NumberFormat(locale, {
                minimumFractionDigits: numericValue % 1 === 0 ? 0 : 1,
                maximumFractionDigits: 1
            }).format(numericValue);
        }

        return String(Math.round(numericValue * 10) / 10);
    }

    function getBreakdownColor(key) {
        switch (key) {
            case "sale":
            case "available":
            case "published":
            case "finished":
                return "#10b981";
            case "rent":
            case "rented":
                return "#6366f1";
            case "featured":
                return "#8b5cf6";
            case "sold":
            case "in_progress":
                return "#f59e0b";
            case "not_started":
                return "#64748b";
            case "draft":
                return "#475569";
            case "not_featured":
                return "#94a3b8";
            case "unknown":
            default:
                return "#cbd5e1";
        }
    }

    var trendCanvas = document.querySelector("canvas[data-dashboard-chart-switcher]");
    var trendButtons = document.querySelectorAll("[data-dashboard-trend-key]");
    var trendEmptyState = document.querySelector(".dashboard-trend-empty");
    var trendSummaryLabel = document.getElementById("dashboard-trend-summary-label");
    var trendSummaryValue = document.getElementById("dashboard-trend-summary-value");
    var trendSummaryYear = document.getElementById("dashboard-trend-summary-year");
    var activeTrendChart = null;

    function visibleTrendSeries(series) {
        var seriesLabels = Array.isArray(series.labels) ? series.labels.slice() : [];
        var seriesValues = Array.isArray(series.values) ? series.values.slice() : [];
        var currentMonth = trendCanvas ? Number(trendCanvas.getAttribute("data-dashboard-current-month")) : 0;
        var currentYear = trendCanvas ? Number(trendCanvas.getAttribute("data-dashboard-current-year")) : 0;
        var limit = Math.min(seriesLabels.length, seriesValues.length);

        if (Number(series.year) === currentYear && currentMonth > 0) {
            limit = Math.min(limit, currentMonth);
        }

        return {
            labels: seriesLabels.slice(0, limit),
            values: seriesValues.slice(0, limit)
        };
    }

    function setActiveTrendButton(activeKey) {
        Array.prototype.forEach.call(trendButtons, function (button) {
            var isActive = button.getAttribute("data-dashboard-trend-key") === activeKey;
            button.classList.toggle("is-active", isActive);
            button.setAttribute("aria-pressed", isActive ? "true" : "false");
        });
    }

    function renderTrendChart(key) {
        var series = charts[key];
        var activeButton = document.querySelector('[data-dashboard-trend-key="' + key + '"]');
        var visibleSeries;
        var context;
        var palette;
        var theme;
        var hasData;

        if (!trendCanvas || !activeButton || !series || !Array.isArray(series.labels) || !Array.isArray(series.values)) {
            return;
        }

        visibleSeries = visibleTrendSeries(series);
        hasData = visibleSeries.values.some(function (value) {
            return Number(value) !== 0;
        });

        setActiveTrendButton(key);
        trendSummaryLabel.textContent = activeButton.getAttribute("data-dashboard-trend-title") || labels[key] || key;
        trendSummaryValue.textContent = formatInteger(activeButton.getAttribute("data-dashboard-trend-total"));
        trendSummaryYear.textContent = activeButton.getAttribute("data-dashboard-trend-period") || "";
        trendCanvas.setAttribute("aria-label", activeButton.getAttribute("data-dashboard-trend-title") || labels[key] || key);
        trendCanvas.setAttribute("aria-describedby", activeButton.getAttribute("data-dashboard-trend-summary") || "");

        if (activeTrendChart) {
            activeTrendChart.destroy();
            activeTrendChart = null;
        }

        trendCanvas.hidden = !hasData;
        if (trendEmptyState) {
            trendEmptyState.hidden = hasData;
        }

        if (!hasData) {
            return;
        }

        context = trendCanvas.getContext("2d");
        if (!context) {
            return;
        }

        palette = getDatasetPalette(context, trendCanvas, key);
        theme = getChartTheme();

        try {
            activeTrendChart = new Chart(context, {
                type: "line",
                data: {
                    labels: visibleSeries.labels,
                    datasets: [{
                        label: labels[key] || key,
                        data: visibleSeries.values,
                        borderColor: palette.line,
                        backgroundColor: palette.fill,
                        pointBackgroundColor: palette.line,
                        pointBorderColor: theme.pointStroke,
                        pointBorderWidth: 2,
                        pointHoverRadius: 6,
                        pointHoverBorderWidth: 2,
                        pointRadius: prefersReducedMotion ? 2 : 3,
                        borderWidth: 3,
                        fill: true,
                        lineTension: prefersReducedMotion ? 0 : 0.18
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: { duration: prefersReducedMotion ? 0 : 420 },
                    hover: { animationDuration: prefersReducedMotion ? 0 : 180 },
                    responsiveAnimationDuration: prefersReducedMotion ? 0 : 180,
                    legend: { display: false },
                    tooltips: {
                        mode: "index",
                        intersect: false,
                        backgroundColor: theme.tooltipBg,
                        titleFontColor: theme.tooltipText,
                        bodyFontColor: theme.tooltipText,
                        xPadding: 12,
                        yPadding: 10,
                        caretPadding: 8,
                        cornerRadius: 10,
                        displayColors: false,
                        rtl: isRtl,
                        textDirection: isRtl ? "rtl" : "ltr",
                        callbacks: {
                            label: function (tooltipItem, data) {
                                var dataset = data.datasets[tooltipItem.datasetIndex] || {};
                                var labelText = dataset.label ? dataset.label + ": " : "";
                                return labelText + formatInteger(tooltipItem.yLabel);
                            }
                        }
                    },
                    scales: {
                        xAxes: [{
                            gridLines: { display: false, drawBorder: false },
                            ticks: {
                                fontColor: theme.muted,
                                maxTicksLimit: 9,
                                minRotation: 0,
                                maxRotation: 0,
                                padding: 10
                            }
                        }],
                        yAxes: [{
                            gridLines: {
                                color: theme.grid,
                                zeroLineColor: theme.grid,
                                drawBorder: false
                            },
                            ticks: {
                                beginAtZero: true,
                                precision: 0,
                                fontColor: theme.muted,
                                maxTicksLimit: 5,
                                padding: 8,
                                callback: function (value) {
                                    return formatInteger(value);
                                }
                            }
                        }]
                    },
                    elements: { point: { hitRadius: 12 } },
                    layout: { padding: { left: 6, right: 6, top: 10, bottom: 0 } }
                }
            });
        } catch (error) {
            trendCanvas.hidden = true;
            if (trendEmptyState) {
                trendEmptyState.hidden = false;
            }
        }
    }

    if (trendCanvas) {
        Array.prototype.forEach.call(trendButtons, function (button) {
            button.addEventListener("click", function () {
                renderTrendChart(button.getAttribute("data-dashboard-trend-key"));
            });
        });

        renderTrendChart(trendCanvas.getAttribute("data-dashboard-initial-chart"));
    }

    Array.prototype.forEach.call(document.querySelectorAll("canvas[data-dashboard-breakdown]"), function (canvas) {
        var rawPayload = canvas.getAttribute("data-dashboard-breakdown");
        var breakdown;
        var context;
        var theme;
        var items;
        var colorSet;
        var values;

        if (!rawPayload) {
            return;
        }

        try {
            breakdown = JSON.parse(rawPayload);
        } catch (error) {
            return;
        }

        if (!breakdown || !Array.isArray(breakdown.items) || !breakdown.items.length) {
            return;
        }

        items = breakdown.items.filter(function (item) {
            return item && Number(item.value) > 0;
        });

        if (!items.length || Number(breakdown.total) <= 0) {
            return;
        }

        context = canvas.getContext("2d");
        if (!context) {
            return;
        }

        theme = getChartTheme();
        colorSet = items.map(function (item) {
            return getBreakdownColor(item.key);
        });
        values = items.map(function (item) {
            return Number(item.value) || 0;
        });

        try {
            new Chart(context, {
                type: "doughnut",
                plugins: [{
                    beforeDraw: function (chartInstance) {
                        var centerLabel = chartInstance.config.options && chartInstance.config.options.dashboardCenterLabel;
                        var chartArea = chartInstance.chartArea;
                        var ctx = chartInstance.chart.ctx;
                        var width;
                        var height;
                        var fontSize;
                        var subtitleSize;

                        if (!centerLabel || !chartArea || !ctx) {
                            return;
                        }

                        width = chartArea.right - chartArea.left;
                        height = chartArea.bottom - chartArea.top;
                        fontSize = Math.max(16, Math.min(24, Math.round(width / 7.4)));
                        subtitleSize = Math.max(10, Math.min(12, Math.round(width / 15)));

                        ctx.save();
                        ctx.textAlign = "center";
                        ctx.textBaseline = "middle";
                        ctx.direction = isRtl ? "rtl" : "ltr";
                        ctx.fillStyle = getCssVariable("--dashboard-text", "#111827");
                        ctx.font = "700 " + fontSize + "px system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif";
                        ctx.fillText(centerLabel.value, chartArea.left + (width / 2), chartArea.top + (height / 2) - 6);
                        ctx.fillStyle = theme.muted;
                        ctx.font = "600 " + subtitleSize + "px system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif";
                        ctx.fillText(centerLabel.label, chartArea.left + (width / 2), chartArea.top + (height / 2) + 15);
                        ctx.restore();
                    }
                }],
                data: {
                    labels: items.map(function (item) {
                        return item.label;
                    }),
                    datasets: [{
                        data: values,
                        backgroundColor: colorSet,
                        borderColor: getCssVariable("--dashboard-surface", "#ffffff"),
                        borderWidth: 4,
                        hoverBorderColor: getCssVariable("--dashboard-surface", "#ffffff"),
                        hoverBorderWidth: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutoutPercentage: 72,
                    animation: {
                        duration: prefersReducedMotion ? 0 : 520,
                        animateRotate: !prefersReducedMotion,
                        animateScale: false
                    },
                    legend: { display: false },
                    tooltips: {
                        backgroundColor: theme.tooltipBg,
                        titleFontColor: theme.tooltipText,
                        bodyFontColor: theme.tooltipText,
                        xPadding: 12,
                        yPadding: 10,
                        caretPadding: 8,
                        cornerRadius: 10,
                        displayColors: true,
                        rtl: isRtl,
                        textDirection: isRtl ? "rtl" : "ltr",
                        callbacks: {
                            title: function (tooltipItems, data) {
                                var tooltipItem = tooltipItems && tooltipItems[0];
                                return tooltipItem ? data.labels[tooltipItem.index] || "" : "";
                            },
                            label: function (tooltipItem) {
                                var item = items[tooltipItem.index] || {};
                                return [
                                    formatInteger(item.value),
                                    (breakdown.shareLabel || "Share of total") + ": " + formatPercentage(item.percentage) + "%"
                                ];
                            }
                        }
                    },
                    hover: {
                        animationDuration: prefersReducedMotion ? 0 : 180
                    },
                    responsiveAnimationDuration: prefersReducedMotion ? 0 : 180,
                    events: ["mousemove", "mouseout", "click", "touchstart", "touchmove"],
                    dashboardCenterLabel: {
                        value: formatInteger(breakdown.total),
                        label: breakdown.totalLabel || ""
                    }
                }
            });
        } catch (error) {
            return;
        }
    });
})();
