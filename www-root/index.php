<!DOCTYPE html>
<html>
<head>
  <meta http-equiv="content-type" content="text/html; charset=UTF-8">
  <title>E-monitor</title>
  <script type='text/javascript' src='/js/jquery-1.9.1.js'></script>
  <script src="/js/highstock.js"></script>
  <link rel="stylesheet" type="text/css" href="/css/result-light.css">
  <style type='text/css'>
    .chart_box{
      border:1px solid #999;
      margin-top:10px;
    }
  </style>
  </head>
  <body>
    <h1>Darco`s Home e-monitor</h1>
    <div id="container_1" class="chart_box"></div>
    <div id="container_2" class=""></div>
    <div id="container_3" class="chart_box"></div>
    <span id="last_p1"></span>
    <span id="last_t1"></span>
    <span id="last_h1"></span>
    
  <script type='text/javascript'>//<![CDATA[ 
  
  $(function() {
  
  	$.getJSON('http://emon.darcoto.net/data.php', function(data) {
  		
  		var colors = {p1:"#89A54E",p2:"#FF8040",t1:"#4572A7",h1:"#808080"};
  		
      $('#container_1').highcharts('StockChart', {
  			rangeSelector: {
  		        buttons: [{
  		            type: 'hour',
  		            count: 1,
  		            text: '1h'
  		        }, {
  		            type: 'day',
  		            count: 1,
  		            text: '1d'
  		        }, {
  		            type: 'week',
  		            count: 1,
  		            text: '1w'
  		        }, {
  		            type: 'month',
  		            count: 1,
  		            text: '1m'
  		        }, {
  		            type: 'month',
  		            count: 6,
  		            text: '6m'
  		        }, {
  		            type: 'year',
  		            count: 1,
  		            text: '1y'
  		        }, {
  		            type: 'all',
  		            text: 'All'
  		        }],
  		        selected: 1
  		    },
  
  			title : {
  				text : 'E-monitor by minutes'
  			},
        xAxis: {
                type: 'datetime',
                tickInterval: 24 * 3600 * 1000, // one day
                tickWidth: 0,
                gridLineWidth: 2
        },
        yAxis: [{
                title: {
                    text: 'Power',
                    style: {
                        color: colors.p1
                    }
                },
                labels: {
                    format: '{value} kW',
                    style: {
                        color: colors.p1
                    }
                }
                
              },{
                title: {
                    text: 'Temperature',
                    style: {
                        color: colors.t1
                    }
                },
                labels: {
                    format: '{value}°C',
                    style: {
                        color: colors.t1
                    }
                },
                opposite: true
              },{
                title: {
                    text: 'Humidity',
                    style: {
                        color: colors.h1
                    }
                },
                labels: {
                    format: '{value} %',
                    style: {
                        color: colors.h1
                    }
                },
                opposite: true
                
              }],
  			series : [{
          type: 'area',
  				name : 'Power 1',
  				color: colors.p1,
  				data : data.all.p1,
  				tooltip: {
  					valueDecimals: 3,
  					valueSuffix: ' kWh'
  				}
  			},{
          type: 'line',
  				name : 'Power 2',
  				color: colors.p2,
  				data : data.all.p2,
  				tooltip: {
  					valueDecimals: 3,
  					valueSuffix: ' kWh'
  				}
  			},{
          type: 'spline',
  				name : 'Temperature',
  				data : data.all.t1,
  				yAxis: 1,
  				color: colors.t1,
  				tooltip: {
  					valueDecimals: 1,
            valueSuffix: ' °C'
  				}
  			},{
          type: 'spline',
  				name : 'Humidity',
  				data : data.all.h1,
  				yAxis: 2,
  				color: colors.h1,
  				tooltip: {
  					valueDecimals: 1,
  				}
  			}]
  		});
      
      //--------------------------------------------------
      $('#container_2').highcharts({
        title: {
            text : 'E-monitor by days'            
        },
        subtitle: {
            text : 'Power and temperature'
        },
        rangeSelector: {
  		        buttons: [{
  		            type: 'week',
  		            count: 1,
  		            text: '1w'
  		        }, {
  		            type: 'month',
  		            count: 1,
  		            text: '1m'
  		        }, {
  		            type: 'month',
  		            count: 6,
  		            text: '6m'
  		        }, {
  		            type: 'year',
  		            count: 1,
  		            text: '1y'
  		        }, {
  		            type: 'all',
  		            text: 'All'
  		        }],
  		        selected: 3
  		    },
        tooltip: {
            crosshairs: true,
            shared: true
        },        
        xAxis: {
                type: 'datetime',
                tickInterval: 24 * 3600 * 1000, // one week
                tickWidth: 0,
                gridLineWidth: 1,
                labels: {
                    rotation: -45,
                    align: 'right',
                    style: {
                        fontSize: '13px',
                        fontFamily: 'Verdana, sans-serif'
                    }
                }
        },
        yAxis: [{
                title: {
                    text: 'Power',
                    style: {
                        color: colors.p1
                    }
                },
                labels: {
                    format: '{value} kW',
                    style: {
                        color: colors.p1
                    }
                },
                
            },{ // Primary yAxis
                title: {
                    text: 'Temperature',
                    style: {
                        color: colors.t1
                    }
                },
                labels: {
                    format: '{value}°C',
                    style: {
                        color: colors.t1
                    }
                },
                opposite: true
            },{ // Primary yAxis
                labels: {
                    format: '{value} %',
                    style: {
                        color: colors.h1
                    }
                },
                title: {
                    text: 'Humidity',
                    style: {
                        color: colors.h1
                    }
                },
                opposite: true
            }],        
  			series : [{
          type: 'column',
  				name : 'Power 1',
  				data : data.days.p1,
          dataLabels: {
              enabled: true,
              rotation: -90,
              color: '#FFFFFF',
              align: 'right',
              x: 4,
              y: 10,
              style: {
                  fontSize: '13px',
                  fontFamily: 'Verdana, sans-serif',
                  textShadow: '0 0 3px black'
              }
          },          
  				color: colors.p1,
  				tooltip: {
  					valueDecimals: 3,
  					valueSuffix: ' kWh'
  				}
  			},{
          type: 'column',
  				name : 'Power 2',
  				data : data.days.p2,
  				color: colors.p2,
  				tooltip: {
  					valueDecimals: 3,
  					valueSuffix: ' kWh'
  				}
  			},{
          type: 'spline',
  				name : 'Temperature',
  				data : data.days.t1,
  				color: colors.t1,
          yAxis: 1,
  				tooltip: {
  					valueDecimals: 1,
            valueSuffix: ' °C'
  				}
  			},{
          type: 'spline',
  				name : 'Humidity',
  				data : data.days.h1,
          yAxis: 2,
  				color: colors.h1,
  				tooltip: {
  					valueDecimals: 1,
            valueSuffix: ' %'
  				}
  			}]
  		});      

      //--------------------------------------------------
      $('#container_3').highcharts({
  			title : {
  				text : 'E-monitor by hours'
  			},
        xAxis: {

        },
  			series : [{
          type: 'column',
  				name : 'Power',
  				data : data.hours.p1,
  				color: colors.p1,
  				tooltip: {
  					valueDecimals: 2,
            valueSuffix: ' kWh'
  				}
  			},{
          type: 'column',
  				name : 'Power',
  				data : data.hours.p2,
  				color: colors.p2,
  				tooltip: {
  					valueDecimals: 2,
            valueSuffix: ' kWh'
  				}
  			}]
  		});
      
  	}).error(function(p1,p2,p3){ alert(p3)});
  
  });
  /**
   * Dark blue theme for Highcharts JS
   * @author Torstein Honsi
   */

  Highcharts.theme = {
     colors: ["#DDDF0D", "#55BF3B", "#DF5353", "#7798BF", "#aaeeee", "#ff0066", "#eeaaee",
        "#55BF3B", "#DF5353", "#7798BF", "#aaeeee"],
     chart: {
        backgroundColor: {
           linearGradient: [0, 0, 250, 500],
           stops: [
              [0, 'rgb(48, 96, 48)'],
              [1, 'rgb(0, 0, 0)']
           ]
        },
        borderColor: '#000000',
        borderWidth: 2,
        className: 'dark-container',
        plotBackgroundColor: 'rgba(255, 255, 255, .1)',
        plotBorderColor: '#CCCCCC',
        plotBorderWidth: 1
     },
     title: {
        style: {
           color: '#C0C0C0',
           font: 'bold 16px "Trebuchet MS", Verdana, sans-serif'
        }
     },
     subtitle: {
        style: {
           color: '#666666',
           font: 'bold 12px "Trebuchet MS", Verdana, sans-serif'
        }
     },
     xAxis: {
        gridLineColor: '#333333',
        gridLineWidth: 1,
        labels: {
           style: {
              color: '#A0A0A0'
           }
        },
        lineColor: '#A0A0A0',
        tickColor: '#A0A0A0',
        title: {
           style: {
              color: '#CCC',
              fontWeight: 'bold',
              fontSize: '12px',
              fontFamily: 'Trebuchet MS, Verdana, sans-serif'

           }
        }
     },
     yAxis: {
        gridLineColor: '#333333',
        labels: {
           style: {
              color: '#A0A0A0'
           }
        },
        lineColor: '#A0A0A0',
        minorTickInterval: null,
        tickColor: '#A0A0A0',
        tickWidth: 1,
        title: {
           style: {
              color: '#CCC',
              fontWeight: 'bold',
              fontSize: '12px',
              fontFamily: 'Trebuchet MS, Verdana, sans-serif'
           }
        }
     },
     tooltip: {
        backgroundColor: 'rgba(0, 0, 0, 0.75)',
        style: {
           color: '#F0F0F0'
        }
     },
     toolbar: {
        itemStyle: {
           color: 'silver'
        }
     },
     plotOptions: {
        line: {
           dataLabels: {
              color: '#CCC'
           },
           marker: {
              lineColor: '#333'
           }
        },
        spline: {
           marker: {
              lineColor: '#333'
           }
        },
        scatter: {
           marker: {
              lineColor: '#333'
           }
        },
        candlestick: {
           lineColor: 'white'
        }
     },
     legend: {
        itemStyle: {
           font: '9pt Trebuchet MS, Verdana, sans-serif',
           color: '#A0A0A0'
        },
        itemHoverStyle: {
           color: '#FFF'
        },
        itemHiddenStyle: {
           color: '#444'
        }
     },
     credits: {
        style: {
           color: '#666'
        }
     },
     labels: {
        style: {
           color: '#CCC'
        }
     },


     navigation: {
        buttonOptions: {
           symbolStroke: '#DDDDDD',
           hoverSymbolStroke: '#FFFFFF',
           theme: {
              fill: {
                 linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
                 stops: [
                    [0.4, '#606060'],
                    [0.6, '#333333']
                 ]
              },
              stroke: '#000000'
           }
        }
     },

     // scroll charts
     rangeSelector: {
        buttonTheme: {
           fill: {
              linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
              stops: [
                 [0.4, '#888'],
                 [0.6, '#555']
              ]
           },
           stroke: '#000000',
           style: {
              color: '#CCC',
              fontWeight: 'bold'
           },
           states: {
              hover: {
                 fill: {
                    linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
                    stops: [
                       [0.4, '#BBB'],
                       [0.6, '#888']
                    ]
                 },
                 stroke: '#000000',
                 style: {
                    color: 'white'
                 }
              },
              select: {
                 fill: {
                    linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
                    stops: [
                       [0.1, '#000'],
                       [0.3, '#333']
                    ]
                 },
                 stroke: '#000000',
                 style: {
                    color: 'yellow'
                 }
              }
           }
        },
        inputStyle: {
           backgroundColor: '#333',
           color: 'silver'
        },
        labelStyle: {
           color: 'silver'
        }
     },

     navigator: {
        handles: {
           backgroundColor: '#666',
           borderColor: '#AAA'
        },
        outlineColor: '#CCC',
        maskFill: 'rgba(16, 16, 16, 0.5)',
        series: {
           color: '#7798BF',
           lineColor: '#A6C7ED'
        }
     },

     scrollbar: {
        barBackgroundColor: {
              linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
              stops: [
                 [0.4, '#888'],
                 [0.6, '#555']
              ]
           },
        barBorderColor: '#CCC',
        buttonArrowColor: '#CCC',
        buttonBackgroundColor: {
              linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
              stops: [
                 [0.4, '#888'],
                 [0.6, '#555']
              ]
           },
        buttonBorderColor: '#CCC',
        rifleColor: '#FFF',
        trackBackgroundColor: {
           linearGradient: { x1: 0, y1: 0, x2: 0, y2: 1 },
           stops: [
              [0, '#000'],
              [1, '#333']
           ]
        },
        trackBorderColor: '#666'
     },

     // special colors for some of the
     legendBackgroundColor: 'rgba(0, 0, 0, 0.5)',
     legendBackgroundColorSolid: 'rgb(35, 35, 70)',
     dataLabelsColor: '#444',
     textColor: '#C0C0C0',
     maskColor: 'rgba(255,255,255,0.3)'
  };

  // Apply the theme
  var highchartsOptions = Highcharts.setOptions(Highcharts.theme);
  //]]>  
  
  </script>  
  </body>
</html>
