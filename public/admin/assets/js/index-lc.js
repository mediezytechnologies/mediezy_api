(function($) {
  'use strict';



    //Line Chart 2
    var ctx2 = document.getElementById('line-chart-2').getContext("2d");
    var gradientStroke2 = ctx.createLinearGradient(0, 0, 0, 450);
    gradientStroke2.addColorStop(0, '#07be6e');

    var gradientFill2 = ctx.createLinearGradient(0, 0, 0, 450);
    gradientFill2.addColorStop(0, "rgba(7, 190, 110,0.3)");
    gradientFill2.addColorStop(1, "rgba(255,255,255,0)");

    var lineChart2 = new Chart(ctx2, {
      type: 'line',
      data: {
          labels: labels2,
          datasets: [{
              label: "Data",
              borderColor: gradientStroke2,
              pointBorderColor: gradientStroke2,
              pointBackgroundColor: gradientStroke2,
              pointHoverBackgroundColor: gradientStroke2,
              pointHoverBorderColor: gradientStroke2,
              pointBorderWidth: 0,
              pointHoverRadius: 0,
              pointHoverBorderWidth: 0,
              pointRadius: 0,
              fill: true,
              backgroundColor: gradientFill2,
              borderWidth: 2,
              data: data_2
          }]
      },
      options: {
          elements: {
            line: {
                tension: 0
            }
          },
          legend: {
          display: false,
          position: "bottom"
          },
          scales: {
            yAxes: [{
              display: false,
            }],
            xAxes: [{
                display: false,
            }]
          }
        }
    });




})(jQuery);
