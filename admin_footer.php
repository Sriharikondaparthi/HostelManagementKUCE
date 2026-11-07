        </main> <!-- content-view -->
    </div> <!-- dashboard-body -->
</div> <!-- dashboard-layout -->

<!-- footer row -- (optional decoration inside the scrollable content or fixed bottom) -->
<div class="container-fluid py-4" style="background: rgba(0,0,0,0.5); border-top: 1px solid rgba(255,255,255,0.1); backdrop-filter: blur(10px); position:relative; z-index:10;">
    <div class="text-center text-white-50 small">
        <a class="text-info font-weight-bold" href="https://dresut.vercel.app/" target="_blank">ESUT</a> &copy; <?php echo date('Y'); ?> | Professional Hostel Billing Dashboard
    </div>
</div>

<!-- js-scripts -->
	<!-- js -->
	<script type="text/javascript" src="../web_home/js/jquery-2.2.3.min.js"></script>
	<script type="text/javascript" src="../web_home/js/bootstrap.js"></script> 
	
	<!-- banner js (Required for Radial Slider Background) -->
	<script src="../web_home/js/snap.svg-min.js"></script>
	<script src="../web_home/js/main.js"></script> 

	<!-- flexSlider -->
	<script defer src="../web_home/js/jquery.flexslider.js"></script>
	<script type="text/javascript">
		$(window).load(function(){
		  $('.flexslider').flexslider({
			animation: "slide",
			start: function(slider){
			  $('body').removeClass('loading');
			}
		  });
		});
	</script>

	<!-- smooth scrolling -->
	<script src="../web_home/js/SmoothScroll.min.js"></script>
	<script type="text/javascript" src="../web_home/js/move-top.js"></script>
	<script type="text/javascript" src="../web_home/js/easing.js"></script>
	<script type="text/javascript">
		jQuery(document).ready(function($) {
			$(".scroll").click(function(event){
				event.preventDefault();
				$('html,body').animate({scrollTop:$(this.hash).offset().top},1000);
			});
            
            if(typeof $().UItoTop === 'function') {
                $().UItoTop({ easingType: 'easeOutQuart' });
            }
		});
	</script>
<!-- //js-scripts -->

</body>
</html>
