{{-- Legacy: system/category9/yoyaku/time_table.php (CSS en línea para iPad, líneas 298-454). Sin variables. --}}
		#popWindow {
			display:block;
			position: fixed;
			top:0;
			left:0;
			box-sizing: border-box;
			min-width:150px;
			max-width:300px;
			// padding:2px 5px;
			padding: 0;
			margin: 0;
			background:#FFFFFF;
			border:1px solid #999999;
			transition: all 0.3s ease-in;
			overflow: hidden;
		}

		#popWinContents {
			overflow: scroll;
			-webkit-overflow-scrolling: touch!important;
			height: 100vh;
			width: 100vw;
		}

		#popWinWrap {
			width: 99vw!important;
		}

		#popWinContentsIframe {
			width: 80vw;
			// height: fit-content!important;
			display: block;
			margin: 0 auto;
		}
		#appendArea {
			// height: fit-content!important;
		}

		.popWindowFix {
			-webkit-overflow-scrolling: auto;
			overflow-scrolling: auto;
			overflow-y: scroll;
		}

		.popWindowOff {
			transform: translateY(100vh);
		}

		.popWindowIn {
			transform: translateY(0);
		}

		#iPadButtonArea {
			display: flex;
			justify-content: center;
		}

		#iPadCustomer {
			width: 75%;
			height: 85%;
			margin: 50px auto 0;
			padding-bottom: 50px;
			overflow: auto;
			font-size: 16px;
		}

		#iPadCustomerInner {
			padding: 10px;
		}

		#iPadContext {
			width: 25%;
			height: 100%;
			transition: 1s;
		}

		#iPadContextInner {
			border-right: 1px solid #cccccc;
			height: 100vh;
		}

		#iPadContextMenu {
			height: 280px;
			width: 100%;
			margin: 0;
		}

		#iPadContextMenu td {
			border-top: 1px solid #ccc;
			border-bottom: 1px solid #ccc;
		}

		#iPadContextStaffContainer {
			display: grid;
			grid-template-columns : repeat(3,1fr);
			grid-gap: 30px;
			text-align: center;
			margin-top: 30px;
			padding-bottom: 50px;
			padding-right: 1px;
		}
		#iPadContextStaffContainer > div {
			border: 1px solid #ccc;
			display: flex;
			justify-content: center;
			align-items: center;
			height: 30px;
			padding: 13px 3px;
			border-radius: 10px;
			background: #fff;
			color: #4D4D4D;
		}

		#iPadOptionContainer {
			height: 90vh;
			width: 100%;
			overflow-y: auto;
			background: #fff;
			z-index: 2;
			-webkit-overflow-scrolling: touch;
			-webkit-transform:translateZ(0px);
		}

		#closePopWindowButton, #changeEdit {
			/* border: 1px solid #AACCFF; */
			border-radius: 5px;
			width: 75px;
			text-align: center;
			padding: 3px 5px;
			margin: 50px auto;
			display: block!important;
		}

		.closePopWindowButtonRight {
			background: #4D4D4D;
			color: #fff;
			width: 30px;
			height: 30px;
			border-radius: 60%;
			font-size: 22px;
			text-align: center;
			margin: 0 0 20px auto;
		}
		#changeEdit {
			background-color: #AACCFF;
			color: #fff;
		}

		.buttonShadow {
			box-shadow: 5px 4px 9px rgba(0,0,0,0.15);
		}

		.reserveStaffEditSelected {
			background: #FF9900;
		}
