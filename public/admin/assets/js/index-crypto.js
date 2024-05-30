(function($) {
  'use strict';

  $("#news-flash").webTicker();

  //Real time rating chart
  var ratingCtx = document.getElementById("crypto-rating-graph").getContext("2d");
  var gradientStrokePrimary = ratingCtx.createLinearGradient(0, 0, 0, 450);
  gradientStrokePrimary.addColorStop(0, '#00acb1');

  var gradientFillPrimary = ratingCtx.createLinearGradient(0, 0, 0, 450);
  gradientFillPrimary.addColorStop(0, "rgba(53,127,250,0.4)");
  gradientFillPrimary.addColorStop(1, "rgba(255,255,255,0)");

  var time = ["12 AM", "2 AM", "4 AM", "6 AM", "8 AM", "10 AM",
    "12 PM", "2 PM", "4 PM", "6 PM", "8 PM", "10 PM"
  ];

  var data = {

    labels: ["12 AM", "2 AM", "4 AM", "6 AM", "8 AM", "10 AM", "12 PM", "2 PM", "4 PM", "6 PM", "8 PM", "10 PM"],
    datasets: [{
      label: "Price",
      borderColor: gradientStrokePrimary,
      pointBorderWidth: 9,
      pointRadius: 9,
      pointBorderColor: 'transparent',
      pointHoverRadius: 8,
      pointHoverBorderWidth: 3,
      pointHoverBackgroundColor: 'white',
      pointHoverBorderColor: '#00acb1',
      pointBackgroundColor: 'transparent',
      fill: true,
      backgroundColor: gradientFillPrimary,
      borderWidth: 2,
      data: [65, 59, 80, 81, 56, 55, 40, 70, 56, 76, 51, 59]
    }]
  };
  var options = {
    animation: false,
    scaleOverride: true,
    scaleSteps: 10,
    scaleStepWidth: 10,
    scaleStartValue: 0,
    elements: {
      line: {
          tension: 0
      }
    },
    legend: {
      display: false,
      position: "top"
    },
    scales: {
        yAxes: [{
            ticks: {
                beginAtZero: true,
                padding: 20
            },
        }],
        xAxes: [{

            ticks: {
                padding: 20,
                beginAtZero: true,
            }
        }]
    }
  };

  var realTimeChart = new Chart(ratingCtx, {
    type: 'line',
    data: data,
    options: options,
  });




})(jQuery);
