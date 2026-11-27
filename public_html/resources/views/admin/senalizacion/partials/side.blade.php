{{-- inc/side.php: menú lateral de la señalización --}}
<!-- Menu toggle -->
<a href="#menu" id="menuLink" class="menu-link">
	<!-- Hamburger icon -->
	<span></span>
</a>

<div id="menu">
	  <div class="">
		<div class="sidebar-title-wrap">
			<h1 style="margin:20px 0 3px 0;">

			@if (str_contains(request()->getHttpHost(), 'crm-tis.jp'))
				<img src="/signage/css/img/signage_title.png" alt="signage">
			@elseif (str_contains(request()->getHttpHost(), 'crm-s.net'))
				<img src="/signage/css/img/signage_extlink_title_logo.png" alt="signage">
			@endif

			</h1>



			<span>サイネージ管理画面</span>
		</div>
		<ul class="sidebar-menu-list">
			<div class="menu-box">
				<div class="menu-title-box">
					<img src="/signage/css/img/signage_title_key.png" style="width:19px; height:20px;">
					<h2>管理画面</h2>
				</div>

				<ul>
					<li>
						<input
							type="button"
							value="管理画面"
							onClick="location.href='{{ route('admin.senalizacion.index') }}';"
							style="margin-top:20px;"
						>
					</li>
				</ul>
			</div>

			<div class="menu-box">
					<div class="menu-title-box">
					<img src="/signage/css/img/signage_setting.png" style="width:20px; height:20px;">
					<h2>設定画面</h2>
				</div>
				<ul>
					<li>
						<input
							type="button"
							value="端末一覧"
							onClick="location.href='{{ route('admin.senalizacion.terminals.index') }}';"
							style="margin-top:20px;"
						>
					</li>
					<li>
						<input
							type="button"
							value="画像サイズ別一覧"
							onClick="location.href='{{ route('admin.senalizacion.content-categories.index', 'image') }}';"
						>
					</li>
					<li>
						<input
							type="button"
							value="パッケージ一覧"
							onClick="location.href='{{ route('admin.senalizacion.packages.index') }}';"
						>
					</li>
				</ul>


			</div>
				<input
					type="button"
					value=""
					onClick="location.href='/system/select.php';"
					class="signage-button return-button"
				>
		</ul>
	</div>
</div>
