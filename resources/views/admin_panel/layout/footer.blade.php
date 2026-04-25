<!--=========================*
        Scripts
*===========================-->

<!-- Jquery Js -->
<script src="{{ asset('assets/js/jquery.min.js') }}"></script>
<!-- bootstrap 4 js -->
<script src="{{ asset('assets/js/popper.min.js') }}"></script>
<script src="{{ asset('assets/js/bootstrap.min.js') }}"></script>
<!-- Owl Carousel Js -->
<script src="{{ asset('assets/js/owl.carousel.min.js') }}"></script>
<!-- Metis Menu Js -->
<script src="{{ asset('assets/js/metisMenu.min.js') }}"></script>
<!-- SlimScroll Js -->
<script src="{{ asset('assets/js/jquery.slimscroll.min.js') }}"></script>
<!-- Slick Nav -->
<script src="{{ asset('assets/js/jquery.slicknav.min.js') }}"></script>

<!-- start amchart js -->
<script src="{{ asset('assets/vendors/am-charts/js/ammap.js') }}"></script>
<script src="{{ asset('assets/vendors/am-charts/js/worldLow.js') }}"></script>
<script src="{{ asset('assets/vendors/am-charts/js/continentsLow.js') }}"></script>
<script src="{{ asset('assets/vendors/am-charts/js/light.js') }}"></script>
<!-- maps js -->
<script src="{{ asset('assets/js/am-maps.js') }}"></script>

<!--Morris Chart-->
<script src="{{ asset('assets/vendors/charts/morris-bundle/raphael.min.js') }}"></script>
<script src="{{ asset('assets/vendors/charts/morris-bundle/morris.js') }}"></script>

<!--Chart Js-->
<script src="{{ asset('assets/vendors/charts/charts-bundle/Chart.bundle.js') }}"></script>

<!-- C3 Chart -->
<script src="{{ asset('assets/vendors/charts/c3charts/c3.min.js') }}"></script>
<script src="{{ asset('assets/vendors/charts/c3charts/d3-5.4.0.min.js') }}"></script>

<!-- Data Table js -->
<script src="{{ asset('assets/vendors/data-table/js/jquery.dataTables.js') }}"></script>
<script src="{{ asset('assets/vendors/data-table/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('assets/vendors/data-table/js/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ asset('assets/vendors/data-table/js/dataTables.responsive.min.js') }}"></script>
<script src="{{ asset('assets/vendors/data-table/js/responsive.bootstrap.min.js') }}"></script>

<!--Sparkline Chart-->
<script src="{{ asset('assets/vendors/charts/sparkline/jquery.sparkline.js') }}"></script>

<!--Home Script-->
<script src="{{ asset('assets/js/home.js') }}"></script>

<!-- Main Js -->
<script src="{{ asset('assets/js/main.js') }}"></script>

<!-- Notification Count Script -->
<script>
    $(document).ready(function() {
        // Load notification count on page load
        function loadNotificationCount() {
            $.get('{{ route('notifications.count') }}', function(data) {
                if (data.count > 0) {
                    $('.notification-badge').text(data.count).show();
                } else {
                    $('.notification-badge').hide();
                }
            });
        }

        loadNotificationCount();

        // Refresh count every 60 seconds
        setInterval(loadNotificationCount, 60000);
    });
</script>
