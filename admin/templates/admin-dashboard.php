<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Prepare popular books
$popular_books_data = array();
if ( ! empty( $summary['popular_books'] ) ) {
	foreach ( $summary['popular_books'] as $pop ) {
		$cover_url = '';
		$author = '';
		foreach ( $books as $bk ) {
			if ( $bk->title === $pop->title ) {
				$cover_url = $bk->cover_image_url;
				$author = $bk->author;
				break;
			}
		}
		$popular_books_data[] = array(
			'title'  => $pop->title,
			'author' => $author,
			'cover'  => $cover_url,
			'opens'  => $pop->opens
		);
	}
}

// Current User Details
$current_wp_user = wp_get_current_user();
$avatar_url = get_avatar_url( $current_wp_user->ID );
?>

<div class="dlm-tailwind-wrap font-sans text-on-surface bg-background min-h-screen overflow-x-hidden pb-24 md:pb-8 md:flex md:flex-row">
	<!-- Desktop Side Navigation Shell -->
	<aside class="w-[280px] bg-white border-r border-outline-variant/20 hidden md:flex flex-col p-6 gap-2 shrink-0">
		<div class="mb-10 flex items-center gap-3 px-2 sidebar-logo-container relative shrink-0">
			<div class="w-10 h-10 bg-primary rounded-xl flex items-center justify-center text-white shrink-0">
				<i class="fa-solid fa-book-open"></i>
			</div>
			<div class="sidebar-text">
				<h1 class="font-bold text-lg text-primary tracking-tight leading-none"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></h1>
				<p class="text-[10px] text-secondary uppercase tracking-[0.2em] mt-1">Library Admin</p>
			</div>
			<button id="sidebar-toggle" class="absolute -right-3 top-1/2 -translate-y-1/2 w-6 h-6 bg-white border border-outline-variant/30 rounded-full flex items-center justify-center text-secondary hover:text-primary hover:shadow-md transition-all z-50">
				<i class="fa-solid fa-chevron-left text-sm transition-transform duration-300"></i>
			</button>
		</div>
		<nav class="flex-1 space-y-1">
			<a class="flex items-center gap-3 px-4 py-3 text-secondary hover:bg-surface-container-low/50 hover:text-on-surface transition-all rounded-lg cursor-pointer nav-active" data-nav="dashboard" onclick="navigateSpa('dashboard')">
				<i class="fa-solid fa-gauge-high shrink-0"></i>
				<span class="text-sm font-semibold sidebar-text">Dashboard</span>
			</a>
			<a class="flex items-center gap-3 px-4 py-3 text-secondary hover:bg-surface-container-low/50 hover:text-on-surface transition-all rounded-lg cursor-pointer" data-nav="books" onclick="navigateSpa('books')">
				<i class="fa-solid fa-book shrink-0"></i>
				<span class="text-sm font-semibold sidebar-text">Books</span>
			</a>
			<a class="flex items-center gap-3 px-4 py-3 text-secondary hover:bg-surface-container-low/50 hover:text-on-surface transition-all rounded-lg cursor-pointer" data-nav="members" onclick="navigateSpa('members')">
				<i class="fa-solid fa-users shrink-0"></i>
				<span class="text-sm font-semibold sidebar-text">Members</span>
				<?php 
				$pending_members = 0;
				if ( ! empty( $subscribers ) ) {
					foreach ( $subscribers as $s ) {
						if ( $s->status === 'pending_approval' ) {
							$pending_members++;
						}
					}
				}
				if ( $pending_members > 0 ) : ?>
					<span class="bg-amber-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full shrink-0 ml-auto"><?php echo intval( $pending_members ); ?></span>
				<?php endif; ?>
			</a>
			<a class="flex items-center gap-3 px-4 py-3 text-secondary hover:bg-surface-container-low/50 hover:text-on-surface transition-all rounded-lg cursor-pointer" data-nav="transactions" onclick="navigateSpa('transactions')">
				<i class="fa-solid fa-receipt shrink-0"></i>
				<span class="text-sm font-semibold sidebar-text">Transactions</span>
				<?php 
				$pending_tx = 0;
				if ( ! empty( $summary['transactions'] ) ) {
					foreach ( $summary['transactions'] as $tx ) {
						if ( $tx->status === 'waiting_approval' ) {
							$pending_tx++;
						}
					}
				}
				if ( $pending_tx > 0 ) : ?>
					<span class="bg-error text-white text-[10px] font-bold px-2 py-0.5 rounded-full shrink-0 ml-auto"><?php echo intval( $pending_tx ); ?></span>
				<?php endif; ?>
			</a>
			<a class="flex items-center gap-3 px-4 py-3 text-secondary hover:bg-surface-container-low/50 hover:text-on-surface transition-all rounded-lg cursor-pointer" data-nav="analytics" onclick="navigateSpa('analytics')">
				<i class="fa-solid fa-chart-line shrink-0"></i>
				<span class="text-sm font-semibold sidebar-text">Analytics</span>
			</a>
			<a class="flex items-center gap-3 px-4 py-3 text-secondary hover:bg-surface-container-low/50 hover:text-on-surface transition-all rounded-lg cursor-pointer" data-nav="settings" onclick="navigateSpa('settings')">
				<i class="fa-solid fa-gear shrink-0"></i>
				<span class="text-sm font-semibold sidebar-text">Settings</span>
			</a>
			<a href="<?php echo esc_url( admin_url() ); ?>" class="flex items-center gap-3 px-4 py-3 text-secondary hover:bg-surface-container-low/50 hover:text-on-surface transition-all rounded-lg cursor-pointer">
				<i class="fa-solid fa-arrow-left shrink-0"></i>
				<span class="text-sm font-semibold sidebar-text">Back to WP Admin</span>
			</a>
		</nav>
		<div class="mt-auto border-t border-outline-variant/20 pt-4 flex items-center gap-3 px-2 sidebar-user-container shrink-0">
			<img class="w-10 h-10 rounded-full object-cover border border-outline-variant/50 shrink-0" src="<?php echo esc_url( $avatar_url ); ?>" alt="Admin Profile">
			<div class="sidebar-text min-w-0">
				<p class="font-semibold text-sm leading-tight truncate text-on-surface"><?php echo esc_html( $current_wp_user->display_name ); ?></p>
				<p class="text-[10px] text-secondary uppercase tracking-wider truncate">Administrator</p>
			</div>
		</div>
	</aside>

	<!-- Main Content Canvas -->
	<main class="flex-grow min-h-screen">
		<!-- SECTION 1: DASHBOARD -->
		<section id="sec-dashboard" class="spa-section pt-10 px-6 md:px-12 space-y-6 max-w-[1440px] mx-auto">
			<div class="flex justify-between items-end mb-4">
				<div>
					<h2 class="text-2xl font-bold text-on-surface">Overview</h2>
					<p class="text-secondary text-sm">Real-time platform performance and insights.</p>
				</div>
				<div class="flex gap-2">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="dlm_export_subscribers">
						<?php wp_nonce_field( 'dlm_export_subscribers_nonce', 'dlm_nonce' ); ?>
						<button type="submit" class="bg-primary text-white px-4 py-2 rounded-lg text-xs font-bold hover:opacity-90 transition-opacity">Export CSV</button>
					</form>
				</div>
			</div>

			<!-- Key Metrics Bento Row -->
			<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
				<!-- Stat Card 1: Total Sales -->
				<div class="bento-card bg-white p-6 rounded-2xl border border-outline-variant/20 shadow-sm flex flex-col justify-between h-36">
					<div class="flex justify-between items-start">
						<p class="text-secondary text-[11px] uppercase tracking-wider font-bold">Total Sales</p>
						<i class="fa-solid fa-wallet text-accent"></i>
					</div>
					<div class="flex items-baseline gap-2 mt-2">
						<h3 class="text-3xl font-bold text-on-surface"><?php echo esc_html( number_format( $summary['total_sales'], 2 ) ) . ' ' . esc_html( $currency ); ?></h3>
					</div>
				</div>
				<!-- Stat Card 2: Active Subscribers -->
				<div class="bento-card bg-white p-6 rounded-2xl border border-outline-variant/20 shadow-sm flex flex-col justify-between h-36">
					<div class="flex justify-between items-start">
						<p class="text-secondary text-[11px] uppercase tracking-wider font-bold">Active Subscribers</p>
						<i class="fa-solid fa-users-line text-primary"></i>
					</div>
					<div class="flex items-baseline gap-2 mt-2">
						<h3 class="text-3xl font-bold text-on-surface"><?php echo esc_html( $summary['active_subscribers'] ); ?></h3>
					</div>
				</div>
				<!-- Stat Card 3: MRR -->
				<div class="bento-card bg-white p-6 rounded-2xl border border-outline-variant/20 shadow-sm flex flex-col justify-between h-36">
					<div class="flex justify-between items-start">
						<p class="text-secondary text-[11px] uppercase tracking-wider font-bold">MRR (30 Days)</p>
						<i class="fa-solid fa-arrow-trend-up text-primary font-bold"></i>
					</div>
					<div class="flex items-baseline gap-2 mt-2">
						<h3 class="text-3xl font-bold text-on-surface"><?php echo esc_html( number_format( $summary['mrr'], 2 ) ) . ' ' . esc_html( $currency ); ?></h3>
					</div>
				</div>
				<!-- Stat Card 4: Churn Rate -->
				<div class="bento-card bg-white p-6 rounded-2xl border border-outline-variant/20 shadow-sm flex flex-col justify-between h-36">
					<div class="flex justify-between items-start">
						<p class="text-secondary text-[11px] uppercase tracking-wider font-bold">Churn Rate</p>
						<i class="fa-solid fa-arrow-trend-down text-error"></i>
					</div>
					<div class="flex items-baseline gap-2 mt-2">
						<h3 class="text-3xl font-bold text-on-surface">1.2%</h3>
						<span class="text-xs font-semibold text-green-600 bg-green-50 px-1.5 rounded">Stable</span>
					</div>
				</div>
			</div>

			<!-- Dashboard Row 2 -->
			<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
				<!-- Revenue Growth SVG line placeholder -->
				<div class="lg:col-span-2 bento-card bg-white p-8 pb-16 rounded-2xl border border-outline-variant/20 shadow-sm relative overflow-hidden h-[340px]">
					<div class="flex flex-col h-full">
						<div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
							<div>
								<h4 class="text-lg font-bold text-on-surface">Revenue Growth</h4>
								<p class="text-secondary text-xs mt-1">Monthly sales volume across subscriptions.</p>
							</div>
							<div class="flex bg-surface-container-low p-1 rounded-xl border border-outline-variant/10">
								<button class="px-4 py-1.5 text-[11px] font-bold uppercase rounded-lg bg-white text-primary shadow-sm transition-all" id="btn-rev-monthly" onclick="toggleDashboardRevenue('monthly')">Monthly</button>
								<button class="px-4 py-1.5 text-[11px] font-bold uppercase rounded-lg text-secondary hover:text-on-surface transition-all" id="btn-rev-yearly" onclick="toggleDashboardRevenue('yearly')">Yearly</button>
							</div>
						</div>
						<div class="flex-1 relative min-h-[160px] mt-2">
							<svg class="w-full h-full overflow-visible" preserveAspectRatio="none" viewBox="0 0 800 200">
								<defs>
									<linearGradient id="amberGradientNew" x1="0" x2="0" y1="0" y2="1">
										<stop offset="0%" stop-color="#855300" stop-opacity="0.2"></stop>
										<stop offset="100%" stop-color="#855300" stop-opacity="0"></stop>
									</linearGradient>
								</defs>
								<line stroke="#f3f3f3" stroke-dasharray="4 4" stroke-width="1" x1="0" x2="800" y1="50" y2="50"></line>
								<line stroke="#f3f3f3" stroke-dasharray="4 4" stroke-width="1" x1="0" x2="800" y1="100" y2="100"></line>
								<line stroke="#f3f3f3" stroke-dasharray="4 4" stroke-width="1" x1="0" x2="800" y1="150" y2="150"></line>
								<path id="rev-path-fill" d="M0 180 Q100 150 200 160 T400 100 T600 80 T800 40 L800 200 L0 200 Z" fill="url(#amberGradientNew)"></path>
								<path id="rev-path-stroke" d="M0 180 Q100 150 200 160 T400 100 T600 80 T800 40" fill="none" stroke="#855300" stroke-linecap="round" stroke-linejoin="round" stroke-width="3"></path>
								<g id="rev-circles">
									<circle class="hover:scale-125 transition-transform cursor-pointer" cx="400" cy="100" fill="#855300" r="5" stroke="#ffffff" stroke-width="2"></circle>
									<circle class="hover:scale-125 transition-transform cursor-pointer" cx="800" cy="40" fill="#855300" r="5" stroke="#ffffff" stroke-width="2"></circle>
								</g>
							</svg>
							<div class="flex justify-between mt-6 text-[10px] text-secondary font-bold uppercase tracking-[0.2em] px-2" id="rev-x-labels">
								<span>Jan</span><span>Feb</span><span>Mar</span><span>Apr</span><span>May</span><span>Jun</span><span>Jul</span><span>Aug</span><span>Sep</span><span>Oct</span><span>Nov</span><span>Dec</span>
							</div>
						</div>
					</div>
				</div>

				<!-- Most Read Books Leaderboard -->
				<div class="bento-card bg-white p-6 rounded-2xl border border-outline-variant/20 shadow-sm flex flex-col h-[340px]">
					<div class="flex justify-between items-start mb-6">
						<h4 class="text-sm font-bold text-on-surface">Most Read</h4>
						<a onclick="navigateSpa('books')" class="text-primary text-xs font-semibold hover:underline cursor-pointer">View All</a>
					</div>
					<div class="space-y-5 flex-1 dlm-hover-scrollbar pr-1">
						<?php if ( empty( $popular_books_data ) ) : ?>
							<p class="text-xs text-secondary italic"><?php esc_html_e('No book reads recorded yet.', 'digital-library-membership' ); ?></p>
						<?php else : ?>
							<?php foreach ( $popular_books_data as $pop_bk ) : ?>
								<div class="flex items-center gap-4 group">
									<div class="w-10 h-14 bg-surface-container rounded-md overflow-hidden relative border border-outline-variant/10 shrink-0">
										<?php if ( $pop_bk['cover'] ) : ?>
											<img class="w-full h-full object-cover" src="<?php echo esc_url( $pop_bk['cover'] ); ?>" alt="Book cover">
										<?php else : ?>
											<div class="w-full h-full bg-slate-100 flex items-center justify-center text-[8px] text-secondary"><?php esc_html_e('No Cover', 'digital-library-membership' ); ?></div>
										<?php endif; ?>
									</div>
									<div class="flex-1 min-w-0">
										<p class="font-bold text-[14px] truncate text-on-surface group-hover:text-primary transition-colors"><?php echo esc_html( $pop_bk['title'] ); ?></p>
										<p class="text-[12px] text-secondary truncate"><?php echo esc_html( $pop_bk['author'] ?: __( 'Unknown Author', 'digital-library-membership' ) ); ?></p>
									</div>
									<div class="text-right">
										<p class="text-xs font-semibold text-on-surface"><?php echo esc_html( $pop_bk['opens'] ); ?></p>
										<p class="text-[10px] text-secondary font-bold"><?php esc_html_e('OPENS', 'digital-library-membership' ); ?></p>
									</div>
								</div>
							<?php endforeach; ?>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<!-- Recent Activity Table -->
			<div class="bento-card bg-white rounded-2xl border border-outline-variant/20 shadow-sm overflow-hidden mb-8">
				<div class="px-8 py-6 border-b border-outline-variant/10 flex justify-between items-center">
					<h4 class="text-sm font-bold text-on-surface">Recent Transaction Logs</h4>
				</div>
				<div class="overflow-x-auto">
					<table class="w-full text-left">
						<thead>
							<tr class="bg-surface-container-low/30">
								<th class="px-8 py-4 text-[10px] text-secondary font-bold tracking-[0.1em] uppercase">User ID / Email</th>
								<th class="px-8 py-4 text-[10px] text-secondary font-bold tracking-[0.1em] uppercase">Gateway</th>
								<th class="px-8 py-4 text-[10px] text-secondary font-bold tracking-[0.1em] uppercase">Amount</th>
								<th class="px-8 py-4 text-[10px] text-secondary font-bold tracking-[0.1em] uppercase">Date</th>
								<th class="px-8 py-4 text-[10px] text-secondary font-bold tracking-[0.1em] uppercase text-right">Status</th>
							</tr>
						</thead>
						<tbody class="divide-y divide-outline-variant/10">
							<?php if ( empty( $summary['transactions'] ) ) : ?>
								<tr>
									<td colspan="5" class="px-8 py-4 text-xs text-secondary italic text-center"><?php esc_html_e('No transactions logged yet.', 'digital-library-membership' ); ?></td>
								</tr>
							<?php else : ?>
								<?php 
								foreach ( $summary['transactions'] as $tx ) : 
									$user_data = get_userdata( $tx->user_id );
									$name_display = $user_data ? $user_data->display_name : 'User #' . $tx->user_id;
									$email_display = $user_data ? $user_data->user_email : '—';
									
									$custom_avatar = get_user_meta( $tx->user_id, 'dlm_profile_image', true );
									if ( ! $custom_avatar ) {
										$custom_avatar = get_user_meta( $tx->user_id, 'profile_image', true );
									}
									$avatar_url = $custom_avatar ? $custom_avatar : get_avatar_url( $tx->user_id );
									if ( ! $avatar_url ) {
										$avatar_url = 'https://secure.gravatar.com/avatar/00000000000000000000000000000000?d=mp&f=y';
									}
								?>
									<tr class="hover:bg-surface-container-lowest transition-colors">
										<td class="px-8 py-4">
											<div class="flex items-center gap-3">
												<div class="w-8 h-8 rounded-full overflow-hidden border border-outline-variant/20 shrink-0 bg-surface-container">
													<img class="w-full h-full object-cover" src="<?php echo esc_url( $avatar_url ); ?>" alt="Avatar" onerror="this.src='https://secure.gravatar.com/avatar/00000000000000000000000000000000?d=mp&f=y';">
												</div>
												<div class="text-sm">
													<p class="font-medium text-on-surface leading-tight"><?php echo esc_html( $name_display ); ?></p>
													<p class="text-secondary text-[12px]"><?php echo esc_html( $email_display ); ?></p>
												</div>
											</div>
										</td>
										<td class="px-8 py-4 text-sm uppercase"><?php echo esc_html( $tx->provider ); ?></td>
										<td class="px-8 py-4 text-sm font-semibold"><?php echo esc_html( number_format( $tx->amount, 2 ) ) . ' ' . esc_html( $tx->currency ); ?></td>
										<td class="px-8 py-4 text-sm text-secondary"><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' H:i', strtotime( $tx->created_at ) ) ); ?></td>
										<td class="px-8 py-4 text-right">
											<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase <?php echo $tx->status === 'completed' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'; ?>">
												<?php echo esc_html( $tx->status ); ?>
											</span>
										</td>
									</tr>
								<?php endforeach; ?>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>
		</section>

		<!-- SECTION 2: BOOKS -->
		<section id="sec-books" class="spa-section pt-10 px-6 md:px-12 space-y-6 max-w-[1440px] mx-auto hidden">
			<div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-4">
				<div>
					<h2 class="text-2xl font-bold text-on-surface">Books Catalog</h2>
					<p class="text-secondary text-sm">Upload, edit, and organize physical and digital collection items.</p>
				</div>

				<div class="flex flex-wrap items-center gap-3">
					<div class="flex items-center bg-white border border-outline-variant/30 rounded-xl px-4 py-2.5 flex-grow md:flex-grow-0 group focus-within:border-primary transition-all">
						<i class="fa-solid fa-magnifying-glass text-on-surface-variant mr-3 group-focus-within:text-primary"></i>
						<input id="books-search-input" class="bg-transparent border-none p-0 focus:ring-0 text-sm w-full md:w-64 placeholder:text-on-surface-variant/60" placeholder="Search by title or author..." type="text">
					</div>
					<button data-open-modal="add-book-modal" class="flex items-center gap-2 bg-primary text-white px-6 py-3 rounded-xl font-semibold text-sm hover:shadow-lg transition-all active:scale-95">
						<i class="fa-solid fa-plus text-sm"></i>
						Add Book
					</button>
				</div>
			</div>

			<!-- Stats grid -->
			<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
				<div class="bg-white p-6 rounded-2xl border border-outline-variant/20 shadow-sm flex items-center justify-between">
					<div>
						<p class="text-[10px] font-bold text-secondary uppercase tracking-wider mb-1">Total Books</p>
						<p class="text-2xl font-bold text-on-surface" id="stat-total-books"><?php echo esc_html( $total_books ); ?></p>
					</div>
					<div class="w-10 h-10 rounded-full bg-primary/5 flex items-center justify-center text-primary">
						<i class="fa-solid fa-book"></i>
					</div>
				</div>
				<div class="bg-white p-6 rounded-2xl border border-outline-variant/20 shadow-sm flex items-center justify-between">
					<div>
						<p class="text-[10px] font-bold text-secondary uppercase tracking-wider mb-1">Active Drafts</p>
						<p class="text-2xl font-bold text-on-surface"><?php echo esc_html( $draft_books ); ?></p>
					</div>
					<div class="w-10 h-10 rounded-full bg-secondary-container/30 flex items-center justify-center text-secondary">
						<i class="fa-regular fa-pen-to-square text-base"></i>
					</div>
				</div>
				<div class="bg-white p-6 rounded-2xl border border-outline-variant/20 shadow-sm flex items-center justify-between">
					<div>
						<p class="text-[10px] font-bold text-secondary uppercase tracking-wider mb-1">Published</p>
						<p class="text-2xl font-bold text-on-surface"><?php echo esc_html( $published_books ); ?></p>
					</div>
					<div class="w-10 h-10 rounded-full bg-accent/10 flex items-center justify-center text-accent">
						<i class="fa-solid fa-rotate fa-spin text-base"></i>
					</div>
				</div>
				<div class="bg-white p-6 rounded-2xl border border-outline-variant/20 shadow-sm flex items-center justify-between">
					<div>
						<p class="text-[10px] font-bold text-secondary uppercase tracking-wider mb-1">Authors</p>
						<p class="text-2xl font-bold text-on-surface"><?php echo esc_html( $total_authors ); ?></p>
					</div>
					<div class="w-10 h-10 rounded-full bg-accent/10 flex items-center justify-center text-accent">
						<i class="fa-solid fa-users text-base"></i>
					</div>
				</div>
			</div>

			<!-- Table -->
			<div class="bg-white rounded-2xl border border-outline-variant/20 shadow-sm overflow-hidden mb-8">
				<div class="overflow-x-auto">
					<table id="books-table" class="w-full text-left border-collapse">
						<thead>
							<tr class="border-b border-outline-variant/10 bg-surface-container-low/50">
								<th class="px-8 py-5 text-[11px] font-bold text-on-surface-variant uppercase tracking-widest">Cover</th>
								<th class="px-6 py-5 text-[11px] font-bold text-on-surface-variant uppercase tracking-widest">Title & Author</th>
								<th class="px-6 py-5 text-[11px] font-bold text-on-surface-variant uppercase tracking-widest">Type</th>
								<th class="px-6 py-5 text-[11px] font-bold text-on-surface-variant uppercase tracking-widest">Status</th>
								<th class="px-6 py-5 text-[11px] font-bold text-on-surface-variant uppercase tracking-widest">Date Added</th>
								<th class="px-8 py-5 text-[11px] font-bold text-on-surface-variant uppercase tracking-widest text-right">Actions</th>
							</tr>
						</thead>
						<tbody class="divide-y divide-outline-variant/10">
							<?php if ( empty( $books ) ) : ?>
								<tr>
									<td colspan="6" class="px-8 py-10 text-center text-xs text-secondary italic"><?php esc_html_e('No books uploaded yet.', 'digital-library-membership' ); ?></td>
								</tr>
							<?php else : ?>
								<?php foreach ( $books as $bk ) : 
									$cats = wp_get_object_terms( $bk->id, 'dlm_book_category' );
									$tags = wp_get_object_terms( $bk->id, 'dlm_book_tag' );
									$cat_id = ( ! is_wp_error( $cats ) && ! empty( $cats ) ) ? $cats[0]->term_id : '';
									$tags_csv = ( ! is_wp_error( $tags ) && ! empty( $tags ) ) ? implode( ', ', wp_list_pluck( $tags, 'name' ) ) : '';
								?>
									<tr class="hover:bg-surface-container-low/30 transition-colors group" 
										data-id="<?php echo intval( $bk->id ); ?>"
										data-title="<?php echo esc_attr( $bk->title ); ?>"
										data-author="<?php echo esc_attr( $bk->author ); ?>"
										data-description="<?php echo esc_attr( $bk->description ); ?>"
										data-cover="<?php echo esc_url( $bk->cover_image_url ); ?>"
										data-status="<?php echo esc_attr( $bk->status ); ?>"
										data-category="<?php echo esc_attr( $cat_id ); ?>"
										data-tags="<?php echo esc_attr( $tags_csv ); ?>"
									>
										<td class="px-8 py-4">
											<div class="w-14 h-20 rounded-lg shadow-md overflow-hidden bg-surface-variant shrink-0">
												<?php if ( $bk->cover_image_url ) : ?>
													<img class="w-full h-full object-cover" src="<?php echo esc_url( $bk->cover_image_url ); ?>" alt="Cover">
												<?php else : ?>
													<div class="w-full h-full bg-slate-100 flex items-center justify-center text-[10px] text-secondary"><?php esc_html_e('No Cover', 'digital-library-membership' ); ?></div>
												<?php endif; ?>
											</div>
										</td>
										<td class="px-6 py-4">
											<div class="flex flex-col">
												<span class="font-bold text-on-surface text-body-lg mb-0.5"><?php echo esc_html( $bk->title ); ?></span>
												<span class="text-sm text-on-surface-variant"><?php echo esc_html( $bk->author ?: __( 'Unknown Author', 'digital-library-membership' ) ); ?></span>
											</div>
										</td>
										<td class="px-6 py-4">
											<span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-sky-100 text-sky-800 uppercase"><?php echo esc_html( $bk->file_type ); ?></span>
										</td>
										<td class="px-6 py-4">
											<?php if ( $bk->status === 'publish' ) : ?>
												<span class="inline-flex items-center px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs font-bold">
													<span class="w-1.5 h-1.5 rounded-full bg-green-500 mr-2"></span>
													Published
												</span>
											<?php else : ?>
												<span class="inline-flex items-center px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-bold">
													<span class="w-1.5 h-1.5 rounded-full bg-slate-500 mr-2"></span>
													Draft
												</span>
											<?php endif; ?>
										</td>
										<td class="px-6 py-4">
											<span class="text-sm text-on-surface-variant"><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $bk->created_at ) ) ); ?></span>
										</td>
										<td class="px-8 py-4 text-right">
											<div class="flex items-center justify-end gap-2">
												<a href="<?php echo esc_url( home_url( '/read/' . $bk->id . '/' ) ); ?>" target="_blank" class="p-2 text-on-surface-variant hover:text-primary hover:bg-primary/5 rounded-lg transition-all" title="View / Read Book">
													<i class="fa-regular fa-eye text-xl"></i>
												</a>
												<button class="p-2 text-on-surface-variant hover:text-primary hover:bg-primary/5 rounded-lg transition-all btn-edit-book" title="Edit Metadata">
													<i class="fa-solid fa-pencil text-xl"></i>
												</button>
												<button class="p-2 text-on-surface-variant hover:text-error hover:bg-error-container/20 rounded-lg transition-all btn-delete-book" title="Delete Book">
													<i class="fa-solid fa-trash-can text-xl"></i>
												</button>
											</div>
										</td>
									</tr>
								<?php endforeach; ?>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>
		</section>

		<!-- SECTION 3: MEMBERS -->
		<section id="sec-members" class="spa-section pt-10 px-6 md:px-12 space-y-6 max-w-[1440px] mx-auto hidden">
			<div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-4">
				<div>
					<h2 class="text-2xl font-bold text-on-surface">Members Directory</h2>
					<p class="text-secondary text-sm">Manage user subscriber tiers, billing intervals, or manually add subscriptions.</p>
				</div>
				<div>
					<button data-open-modal="add-member-modal" class="flex items-center gap-2 bg-primary text-white px-6 py-3 rounded-xl font-semibold text-sm hover:shadow-lg transition-all active:scale-95">
						<i class="fa-solid fa-plus text-sm"></i>
						Add Member
					</button>
				</div>
			</div>



			<!-- Members list -->
			<div class="bg-white rounded-2xl border border-outline-variant/20 shadow-sm overflow-hidden mb-8">
				<div class="overflow-x-auto">
					<table id="members-table" class="w-full text-left border-collapse">
						<thead>
							<tr class="border-b border-outline-variant/10 bg-surface-container-low/50">
								<th class="px-8 py-5 text-[11px] font-bold text-on-surface-variant uppercase tracking-widest">User ID / Name</th>
								<th class="px-8 py-5 text-[11px] font-bold text-on-surface-variant uppercase tracking-widest">Gateway</th>
								<th class="px-8 py-5 text-[11px] font-bold text-on-surface-variant uppercase tracking-widest">Billing Tier</th>
								<th class="px-8 py-5 text-[11px] font-bold text-on-surface-variant uppercase tracking-widest">Status</th>
								<th class="px-8 py-5 text-[11px] font-bold text-on-surface-variant uppercase tracking-widest">Expires At</th>
								<th class="px-8 py-5 text-[11px] font-bold text-on-surface-variant uppercase tracking-widest text-right">Actions</th>
							</tr>
						</thead>
						<tbody class="divide-y divide-outline-variant/10">
							<?php if ( empty( $subscribers ) ) : ?>
								<tr>
									<td colspan="6" class="px-8 py-10 text-center text-xs text-secondary italic"><?php esc_html_e('No registered members found.', 'digital-library-membership' ); ?></td>
								</tr>
							<?php else : ?>
								<?php foreach ( $subscribers as $sub ) : 
									$avatar = get_avatar_url( $sub->user_id );
									$is_expired = strtotime( $sub->expires_at ) < time();
									
									if ( $sub->status === 'pending_approval' ) {
										$status_badge_class = 'bg-amber-100 text-amber-700';
									} else {
										$status_badge_class = ( $sub->status === 'active' && ! $is_expired ) ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700';
									}
								?>
									<tr class="hover:bg-surface-container-low/20 transition-colors group"
										data-db-id="<?php echo intval( $sub->id ); ?>"
										data-user-id="<?php echo intval( $sub->user_id ); ?>"
										data-name="<?php echo esc_attr( $sub->display_name ); ?>"
										data-email="<?php echo esc_attr( $sub->user_email ); ?>"
										data-tier="<?php echo esc_attr( $sub->plan_interval ); ?>"
										data-status="<?php echo esc_attr( $sub->status ); ?>"
										data-expires="<?php echo $sub->expires_at !== '0000-00-00 00:00:00' ? esc_attr( wp_date( 'Y-m-d', strtotime( $sub->expires_at ) ) ) : ''; ?>"
									>
										<td class="px-8 py-4">
											<div class="flex items-center gap-3">
												<div class="w-10 h-10 rounded-full overflow-hidden border border-outline-variant/20 shrink-0">
													<img class="w-full h-full object-cover" src="<?php echo esc_url( $avatar ); ?>" alt="Avatar">
												</div>
												<div>
													<p class="font-title-sm text-on-surface text-[15px] font-bold"><?php echo esc_html( $sub->display_name ); ?></p>
													<p class="font-body-md text-secondary text-[13px]"><?php echo esc_html( $sub->user_email ); ?></p>
												</div>
											</div>
										</td>
										<td class="px-8 py-4 font-semibold uppercase text-xs text-secondary"><?php echo esc_html( $sub->provider ); ?></td>
										<td class="px-8 py-4">
											<span class="px-3 py-1 rounded-full font-label-caps text-[11px] font-bold <?php echo $sub->plan_interval === 'lifetime' ? 'bg-primary/10 text-primary' : 'bg-outline-variant/30 text-secondary'; ?>">
												<?php echo esc_html( ucfirst( $sub->plan_interval ) ); ?>
											</span>
										</td>
										<td class="px-8 py-4">
											<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase <?php echo esc_attr( $status_badge_class ); ?>">
												<?php echo esc_html( $sub->status ); ?>
											</span>
										</td>
										<td class="px-8 py-4 text-sm text-secondary">
											<?php 
											if ( $sub->plan_interval === 'lifetime' && $sub->status === 'active' ) {
												esc_html_e('Lifetime Access', 'digital-library-membership' );
											} else {
												echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $sub->expires_at ) ) );
											}
											?>
										</td>
										<td class="px-8 py-4 text-right">
											<div class="flex items-center justify-end gap-2">
												<?php if ( $sub->status === 'pending_approval' ) : ?>
													<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="inline-block">
														<input type="hidden" name="action" value="dlm_approve_subscription">
														<input type="hidden" name="subscription_db_id" value="<?php echo intval( $sub->id ); ?>">
														<?php wp_nonce_field( 'dlm_approve_subscription_nonce', 'dlm_nonce' ); ?>
														<button type="submit" class="bg-primary text-white text-[10px] font-bold px-2 py-1 rounded hover:opacity-90 transition-opacity uppercase"><?php esc_html_e('Approve', 'digital-library-membership' ); ?></button>
													</form>
													<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="inline-block ml-1">
														<input type="hidden" name="action" value="dlm_reject_subscription">
														<input type="hidden" name="subscription_db_id" value="<?php echo intval( $sub->id ); ?>">
														<?php wp_nonce_field( 'dlm_reject_subscription_nonce', 'dlm_nonce' ); ?>
														<button type="submit" class="border border-error text-error text-[10px] font-bold px-2 py-1 rounded hover:bg-error-container/20 transition-all uppercase"><?php esc_html_e('Reject', 'digital-library-membership' ); ?></button>
													</form>
												<?php endif; ?>
												<button class="p-1.5 text-secondary hover:text-primary hover:bg-primary/5 rounded-lg transition-all btn-send-email" title="Send Email">
													<i class="fa-regular fa-envelope text-[20px]"></i>
												</button>
												<button class="p-1.5 text-secondary hover:bg-surface-container-high/50 rounded-lg transition-colors btn-edit-member" title="Edit Override">
													<i class="fa-solid fa-pencil text-[20px]"></i>
												</button>
												<button class="p-1.5 text-error-red/75 hover:text-error-red hover:bg-error-container/20 rounded-lg transition-colors btn-delete-member" title="Remove Record">
													<i class="fa-solid fa-trash-can text-[20px]"></i>
												</button>
											</div>
										</td>
									</tr>
								<?php endforeach; ?>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>
		</section>

		<!-- SECTION 3B: ORDER TRANSACTIONS -->
		<section id="sec-transactions" class="spa-section pt-10 px-6 md:px-12 space-y-6 max-w-[1440px] mx-auto hidden">
			<div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-4">
				<div>
					<h2 class="text-2xl font-bold text-on-surface">Order Transactions</h2>
					<p class="text-secondary text-sm">Manage payment logs, subscription status approvals, and processing refunds.</p>
				</div>
				<div class="flex flex-wrap items-center gap-3">
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="inline-block">
						<input type="hidden" name="action" value="dlm_export_transactions">
						<?php wp_nonce_field( 'dlm_export_transactions_nonce', 'dlm_nonce' ); ?>
						<button type="submit" class="flex items-center gap-2 border border-outline-variant/30 text-secondary hover:bg-surface-container-low px-5 py-3 rounded-xl font-bold text-sm transition-all">
							<i class="fa-solid fa-download"></i>
							Export CSV
						</button>
					</form>
					<button data-open-modal="add-transaction-modal" class="flex items-center gap-2 bg-primary text-white px-6 py-3 rounded-xl font-semibold text-sm hover:shadow-lg transition-all active:scale-95">
						<i class="fa-solid fa-plus text-sm"></i>
						Add Transaction
					</button>
				</div>
			</div>

			<!-- Transactions Data Table -->
			<div class="bg-white rounded-3xl border border-outline-variant/10 shadow-sm overflow-hidden">
				<div class="overflow-x-auto">
					<table class="w-full text-left border-collapse">
						<thead>
							<tr class="border-b border-outline-variant/10 bg-surface-container-low/50">
								<th class="px-8 py-5 text-[11px] font-bold text-on-surface-variant uppercase tracking-widest">User Details</th>
								<th class="px-8 py-5 text-[11px] font-bold text-on-surface-variant uppercase tracking-widest">Gateway</th>
								<th class="px-8 py-5 text-[11px] font-bold text-on-surface-variant uppercase tracking-widest">Transaction ID</th>
								<th class="px-8 py-5 text-[11px] font-bold text-on-surface-variant uppercase tracking-widest">Amount</th>
								<th class="px-8 py-5 text-[11px] font-bold text-on-surface-variant uppercase tracking-widest">Status</th>
								<th class="px-8 py-5 text-[11px] font-bold text-on-surface-variant uppercase tracking-widest">Date</th>
								<th class="px-8 py-5 text-[11px] font-bold text-on-surface-variant uppercase tracking-widest text-right">Actions</th>
							</tr>
						</thead>
						<tbody class="divide-y divide-outline-variant/10">
							<?php 
							global $wpdb;
							$t_tx = $wpdb->prefix . 'dlm_transactions';
							$txs_list = array();
							// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
							if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $t_tx ) ) === $t_tx ) {
								// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
								$txs_list = $wpdb->get_results(
									$wpdb->prepare(
										"SELECT t.*, u.display_name, u.user_email 
										FROM %i t
										LEFT JOIN %i u ON t.user_id = u.ID
										ORDER BY t.created_at DESC",
										$t_tx,
										$wpdb->users
									)
								);
							}
							
							if ( empty( $txs_list ) ) : ?>
								<tr>
									<td colspan="7" class="px-8 py-10 text-center text-xs text-secondary italic"><?php esc_html_e('No transactions logged yet.', 'digital-library-membership' ); ?></td>
								</tr>
							<?php else : ?>
								<?php foreach ( $txs_list as $tx ) : 
									$avatar = get_avatar_url( $tx->user_id );
									if ( $tx->status === 'completed' || $tx->status === 'approved' ) {
										$badge_class = 'bg-green-100 text-green-700';
										$display_status = 'approved';
									} elseif ( $tx->status === 'waiting_approval' || $tx->status === 'pending' ) {
										$badge_class = 'bg-amber-100 text-amber-700';
										$display_status = 'waiting approval';
									} else {
										$badge_class = 'bg-red-100 text-red-700';
										$display_status = 'refunded';
									}
								?>
									<tr class="hover:bg-surface-container-low/20 transition-colors group"
										data-id="<?php echo intval( $tx->id ); ?>"
										data-user-id="<?php echo intval( $tx->user_id ); ?>"
										data-username="<?php echo esc_attr( $tx->display_name ); ?>"
										data-useremail="<?php echo esc_attr( $tx->user_email ); ?>"
										data-sub-id="<?php echo esc_attr( $tx->subscription_id ); ?>"
										data-tx-id="<?php echo esc_attr( $tx->transaction_id ); ?>"
										data-provider="<?php echo esc_attr( $tx->provider ); ?>"
										data-amount="<?php echo esc_attr( $tx->amount ); ?>"
										data-currency="<?php echo esc_attr( $tx->currency ); ?>"
										data-status="<?php echo esc_attr( $tx->status ); ?>"
										data-date="<?php echo esc_attr( $tx->created_at ); ?>"
									>
										<td class="px-8 py-4">
											<div class="flex items-center gap-3">
												<div class="w-10 h-10 rounded-full overflow-hidden border border-outline-variant/20 shrink-0">
													<img class="w-full h-full object-cover" src="<?php echo esc_url( $avatar ); ?>" alt="Avatar">
												</div>
												<div>
													<p class="font-title-sm text-on-surface text-[15px] font-bold"><?php echo esc_html( $tx->display_name ?: 'Deleted User' ); ?></p>
													<p class="font-body-md text-secondary text-[13px]"><?php echo esc_html( $tx->user_email ?: '—' ); ?></p>
												</div>
											</div>
										</td>
										<td class="px-8 py-4 font-semibold uppercase text-xs text-secondary"><?php echo esc_html( $tx->provider ); ?></td>
										<td class="px-8 py-4 font-semibold text-xs text-on-surface"><?php echo esc_html( $tx->transaction_id ); ?></td>
										<td class="px-8 py-4 font-bold text-sm text-on-surface"><?php echo esc_html( number_format( $tx->amount, 2 ) ) . ' ' . esc_html( $tx->currency ); ?></td>
										<td class="px-8 py-4">
											<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase <?php echo esc_attr( $badge_class ); ?>">
												<?php echo esc_html( $display_status ); ?>
											</span>
										</td>
										<td class="px-8 py-4 text-sm text-secondary"><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' H:i', strtotime( $tx->created_at ) ) ); ?></td>
										<td class="px-8 py-4 text-right">
											<div class="flex items-center justify-end gap-2">
												<button class="p-1.5 text-secondary hover:bg-surface-container-high/50 rounded-lg transition-colors btn-edit-tx" title="Edit/View Transaction">
													<i class="fa-solid fa-pencil text-[20px]"></i>
												</button>
												<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="inline-block" onsubmit="return confirm('Are you sure you want to delete this transaction record?');">
													<input type="hidden" name="action" value="dlm_delete_transaction">
													<input type="hidden" name="id" value="<?php echo intval( $tx->id ); ?>">
													<?php wp_nonce_field( 'dlm_delete_transaction_nonce', 'dlm_nonce' ); ?>
													<button type="submit" class="p-1.5 text-error-red/75 hover:text-error-red hover:bg-error-container/20 rounded-lg transition-colors" title="Delete Transaction">
														<i class="fa-solid fa-trash-can text-[20px]"></i>
													</button>
												</form>
											</div>
										</td>
									</tr>
								<?php endforeach; ?>
							<?php endif; ?>
						</tbody>
					</table>
				</div>
			</div>
		</section>

		<!-- SECTION 4: ANALYTICS -->
		<section id="sec-analytics" class="spa-section pt-10 px-6 md:px-12 space-y-6 max-w-[1440px] mx-auto hidden">
			<div>
				<h2 class="text-2xl font-bold text-on-surface">Platform Analytics</h2>
				<p class="text-secondary text-sm">Real-time performance overview, sales patterns, and engagement benchmarks.</p>
			</div>

			<!-- Stats grid -->
			<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
				<div class="bg-white p-6 rounded-2xl border border-outline-variant/10 shadow-sm flex items-center justify-between">
					<div>
						<p class="text-[10px] uppercase tracking-widest text-secondary font-bold mb-1">Total Revenue</p>
						<h3 class="text-2xl font-bold text-on-surface"><?php echo esc_html( number_format( $summary['total_sales'], 2 ) ) . ' ' . esc_html( $currency ); ?></h3>
					</div>
					<div class="w-12 h-12 bg-primary/10 rounded-full flex items-center justify-center">
						<i class="fa-solid fa-wallet text-primary"></i>
					</div>
				</div>
				<div class="bg-white p-6 rounded-2xl border border-outline-variant/10 shadow-sm flex items-center justify-between">
					<div>
						<p class="text-[10px] uppercase tracking-widest text-secondary font-bold mb-1">Active Subscriptions</p>
						<h3 class="text-2xl font-bold text-on-surface"><?php echo esc_html( $summary['active_subscribers'] ); ?></h3>
					</div>
					<div class="w-12 h-12 bg-primary/5 rounded-full flex items-center justify-center">
						<i class="fa-solid fa-star text-primary"></i>
					</div>
				</div>
				<div class="bg-white p-6 rounded-2xl border border-outline-variant/10 shadow-sm flex items-center justify-between">
					<div>
						<p class="text-[10px] uppercase tracking-widest text-secondary font-bold mb-1">Churn Rate</p>
						<h3 class="text-2xl font-bold text-on-surface">1.2%</h3>
						<div class="flex items-center gap-1 mt-2 text-accent">
							<i class="fa-solid fa-minus text-sm"></i>
							<span class="text-xs font-bold">Stable</span>
						</div>
					</div>
					<div class="w-12 h-12 bg-red-50 rounded-full flex items-center justify-center">
						<i class="fa-solid fa-user-slash text-red-400"></i>
					</div>
				</div>
			</div>

			<!-- Charts -->
			<div class="grid grid-cols-12 gap-6">
				<section class="col-span-12 lg:col-span-8 bg-white p-8 rounded-2xl border border-outline-variant/10 shadow-sm h-[500px] flex flex-col relative overflow-hidden">
					<div class="flex justify-between items-center mb-8 relative z-10">
						<div>
							<h4 class="text-lg font-bold text-on-surface mb-1">Revenue Performance</h4>
							<p class="text-xs text-secondary">Historical sales volume and conversion trends.</p>
						</div>
						<div class="flex bg-surface-container-low p-1 rounded-lg">
							<button id="btn-analytics-weekly" onclick="toggleAnalyticsRevenue('weekly')" class="px-4 py-1.5 text-xs font-bold rounded-md bg-white shadow-sm text-primary transition-all">Weekly</button>
							<button id="btn-analytics-monthly" onclick="toggleAnalyticsRevenue('monthly')" class="px-4 py-1.5 text-xs font-bold rounded-md text-secondary hover:text-on-surface transition-all">Monthly</button>
						</div>
					</div>
					<div class="flex-1 w-full relative">
						<canvas id="revenueChart"></canvas>
					</div>
				</section>
				
				<section class="col-span-12 lg:col-span-4 bg-white p-8 rounded-2xl border border-outline-variant/10 shadow-sm h-[500px] flex flex-col">
					<h4 class="text-lg font-bold text-on-surface mb-1">Membership Status</h4>
					<p class="text-xs text-secondary mb-8">Active vs Inactive community ratio.</p>
					<?php
					$total_subs_count = isset( $summary['total_subscribers'] ) ? intval( $summary['total_subscribers'] ) : 0;
					$active_subs_count = isset( $summary['active_subscribers'] ) ? intval( $summary['active_subscribers'] ) : 0;
					$inactive_subs_count = max( 0, $total_subs_count - $active_subs_count );
					$retention_rate = $total_subs_count > 0 ? round( ( $active_subs_count / $total_subs_count ) * 100 ) : 0;
					?>
					<div class="flex-1 flex flex-col items-center justify-center relative">
						<div class="w-48 h-48 relative">
							<canvas id="membershipChart"></canvas>
							<div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
								<span class="text-3xl font-bold text-on-surface"><?php echo esc_html( $retention_rate ) . '%'; ?></span>
								<span class="text-[10px] font-bold text-secondary uppercase tracking-widest">Retention</span>
							</div>
						</div>
					</div>
					<div class="space-y-4 mt-6">
						<div class="flex items-center justify-between p-3 rounded-lg bg-surface-container-low/50">
							<div class="flex items-center gap-3">
								<div class="w-3 h-3 rounded-full bg-accent"></div>
								<span class="text-sm font-medium">Active Members</span>
							</div>
							<span class="text-sm font-bold"><?php echo esc_html( $active_subs_count ); ?></span>
						</div>
						<div class="flex items-center justify-between p-3 rounded-lg border border-outline-variant/10">
							<div class="flex items-center gap-3">
								<div class="w-3 h-3 rounded-full bg-primary/20"></div>
								<span class="text-sm font-medium">Inactive</span>
							</div>
							<span class="text-sm font-bold"><?php echo esc_html( $inactive_subs_count ); ?></span>
						</div>
					</div>
				</section>
			</div>
		</section>

		<!-- SECTION 5: SETTINGS -->
		<section id="sec-settings" class="spa-section pt-10 px-6 md:px-12 space-y-6 max-w-[1440px] mx-auto hidden">
			<div class="mb-4">
				<h2 class="text-2xl font-bold text-on-surface">Settings Panel</h2>
				<p class="text-secondary text-sm">Configure system preferences, payment gateways, and pricing parameters.</p>
				<?php settings_errors(); ?>
			</div>

			<div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
				<!-- Settings Left Tabs -->
				<div class="lg:col-span-4 space-y-3">
					<button onclick="switchSettingsTab('general')" id="tab-settings-general" class="w-full text-left px-5 py-3 rounded-xl font-bold text-sm bg-primary/10 text-primary transition-all flex items-center gap-3">
						<i class="fa-solid fa-gear"></i>
						Pricing & Instructions
					</button>
					<button onclick="switchSettingsTab('stripe')" id="tab-settings-stripe" class="w-full text-left px-5 py-3 rounded-xl font-bold text-sm text-secondary hover:bg-surface-container-low transition-all flex items-center gap-3">
						<i class="fa-solid fa-credit-card"></i>
						Stripe Setup
					</button>
					<button onclick="switchSettingsTab('paypal')" id="tab-settings-paypal" class="w-full text-left px-5 py-3 rounded-xl font-bold text-sm text-secondary hover:bg-surface-container-low transition-all flex items-center gap-3">
						<i class="fa-solid fa-wallet"></i>
						PayPal Setup
					</button>
					<?php if ( class_exists( 'WooCommerce' ) ) : ?>
					<button type="button" onclick="switchSettingsTab('woocommerce')" id="tab-settings-woocommerce" class="w-full text-left px-5 py-3 rounded-xl font-bold text-sm text-secondary hover:bg-surface-container-low transition-all flex items-center gap-3">
						<i class="fa-brands fa-woocommerce"></i>
						WooCommerce Setup
					</button>
					<?php endif; ?>
					<button type="button" onclick="switchSettingsTab('security')" id="tab-settings-security" class="w-full text-left px-5 py-3 rounded-xl font-bold text-sm text-secondary hover:bg-surface-container-low transition-all flex items-center gap-3">
						<i class="fa-solid fa-shield-halved"></i>
						Security & Legal
					</button>
				</div>

				<!-- Settings Forms container -->
				<div class="lg:col-span-8 bg-white border border-outline-variant/20 rounded-2xl p-8 shadow-sm mb-8">
					<form method="post" action="options.php">
						<?php settings_fields( 'dlm_settings_group' ); ?>
						
						<!-- Pricing & Manual Instructions Settings Panel -->
						<div id="panel-settings-general" class="space-y-6">
							<h3 class="text-lg font-bold text-on-surface border-b border-outline-variant/10 pb-3">Pricing & Gateway Settings</h3>
							<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
								<div class="space-y-1">
									<label class="text-xs font-bold text-on-surface-variant uppercase">Currency Code</label>
									<input class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm" type="text" name="dlm_currency" value="<?php echo esc_attr( get_option( 'dlm_currency', 'USD' ) ); ?>" placeholder="e.g. USD">
								</div>
								<div class="space-y-1">
									<label class="text-xs font-bold text-on-surface-variant uppercase">Max Book Upload Size (MB)</label>
									<input class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm" type="number" name="dlm_max_upload_size" value="<?php echo esc_attr( get_option( 'dlm_max_upload_size', '50' ) ); ?>" required>
									<span class="text-[11px] text-secondary block mt-1">Server limit: <?php echo esc_html( $this->get_server_max_upload_size() ); ?> MB</span>
								</div>
								<div class="space-y-1">
									<label class="text-xs font-bold text-on-surface-variant uppercase">Monthly Plan Price ($)</label>
									<input class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm" type="number" step="0.01" name="dlm_pricing_monthly" value="<?php echo esc_attr( get_option( 'dlm_pricing_monthly' ) ); ?>" placeholder="e.g. 9.99">
								</div>
								<div class="space-y-1">
									<label class="text-xs font-bold text-on-surface-variant uppercase">Yearly Plan Price ($)</label>
									<input class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm" type="number" step="0.01" name="dlm_pricing_yearly" value="<?php echo esc_attr( get_option( 'dlm_pricing_yearly' ) ); ?>" placeholder="e.g. 99.99">
								</div>
								<div class="space-y-1">
									<label class="text-xs font-bold text-on-surface-variant uppercase">Lifetime Plan Price ($)</label>
									<input class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm" type="number" step="0.01" name="dlm_pricing_lifetime" value="<?php echo esc_attr( get_option( 'dlm_pricing_lifetime' ) ); ?>" placeholder="e.g. 199.99">
								</div>
							</div>
							<div class="border-t border-outline-variant/10 pt-4 space-y-4">
								<h4 class="text-xs font-bold text-primary uppercase tracking-wider">Configure Frontend Plan Bullet Features (One per line)</h4>
								<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
									<div class="space-y-1">
										<label class="text-xs font-bold text-on-surface-variant uppercase">Monthly Plan Features</label>
										<textarea name="dlm_features_monthly" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-xs font-mono" rows="6" placeholder="One benefit per line..."><?php echo esc_textarea( get_option( 'dlm_features_monthly' ) ); ?></textarea>
									</div>
									<div class="space-y-1">
										<label class="text-xs font-bold text-on-surface-variant uppercase">Yearly Plan Features</label>
										<textarea name="dlm_features_yearly" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-xs font-mono" rows="6" placeholder="One benefit per line..."><?php echo esc_textarea( get_option( 'dlm_features_yearly' ) ); ?></textarea>
									</div>
									<div class="space-y-1">
										<label class="text-xs font-bold text-on-surface-variant uppercase">Lifetime Plan Features</label>
										<textarea name="dlm_features_lifetime" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-xs font-mono" rows="6" placeholder="One benefit per line..."><?php echo esc_textarea( get_option( 'dlm_features_lifetime' ) ); ?></textarea>
									</div>
								</div>
							</div>
							<div class="space-y-2">
								<label class="text-xs font-bold text-on-surface-variant uppercase">Manual Bank Transfer Instructions</label>
								<div class="border border-outline-variant/20 rounded-xl p-2 bg-white">
									<?php
									$instructions = get_option( 'dlm_manual_payment_instructions', '' );
									wp_editor( $instructions, 'dlm_manual_payment_instructions', array( 'textarea_name' => 'dlm_manual_payment_instructions', 'textarea_rows' => 4, 'media_buttons' => false ) );
									?>
								</div>
							</div>
						</div>

						<!-- Stripe Configuration Panel -->
						<div id="panel-settings-stripe" class="space-y-6 hidden">
							<h3 class="text-lg font-bold text-on-surface border-b border-outline-variant/10 pb-3">Stripe Setup</h3>
							<div class="space-y-4">
								<div class="space-y-1">
									<label class="text-xs font-bold text-on-surface-variant uppercase">Stripe Publishable Key</label>
									<input class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm" type="text" name="dlm_stripe_publishable_key" value="<?php echo esc_attr( get_option( 'dlm_stripe_publishable_key' ) ); ?>">
								</div>
								<div class="space-y-1">
									<label class="text-xs font-bold text-on-surface-variant uppercase">Stripe Secret Key</label>
									<input class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm" type="password" name="dlm_stripe_secret_key" value="<?php echo esc_attr( get_option( 'dlm_stripe_secret_key' ) ); ?>">
								</div>
								<div class="space-y-1">
									<label class="text-xs font-bold text-on-surface-variant uppercase">Stripe Monthly Price ID</label>
									<input class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm" type="text" name="dlm_stripe_monthly_price_id" value="<?php echo esc_attr( get_option( 'dlm_stripe_monthly_price_id' ) ); ?>" placeholder="price_xxxxx">
								</div>
								<div class="space-y-1">
									<label class="text-xs font-bold text-on-surface-variant uppercase">Stripe Yearly Price ID</label>
									<input class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm" type="text" name="dlm_stripe_yearly_price_id" value="<?php echo esc_attr( get_option( 'dlm_stripe_yearly_price_id' ) ); ?>" placeholder="price_xxxxx">
								</div>
								<div class="space-y-1">
									<label class="text-xs font-bold text-on-surface-variant uppercase">Stripe Lifetime Price ID</label>
									<input class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm" type="text" name="dlm_stripe_lifetime_price_id" value="<?php echo esc_attr( get_option( 'dlm_stripe_lifetime_price_id' ) ); ?>" placeholder="price_xxxxx">
								</div>
							</div>
						</div>

						<!-- PayPal Configuration Panel -->
						<div id="panel-settings-paypal" class="space-y-6 hidden">
							<h3 class="text-lg font-bold text-on-surface border-b border-outline-variant/10 pb-3">PayPal Setup</h3>
							<div class="space-y-4">
								<div class="space-y-1">
									<label class="text-xs font-bold text-on-surface-variant uppercase">PayPal Client ID</label>
									<input class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm" type="text" name="dlm_paypal_client_id" value="<?php echo esc_attr( get_option( 'dlm_paypal_client_id' ) ); ?>">
								</div>
								<div class="space-y-1">
									<label class="text-xs font-bold text-on-surface-variant uppercase">PayPal Secret Key</label>
									<input class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm" type="password" name="dlm_paypal_secret_key" value="<?php echo esc_attr( get_option( 'dlm_paypal_secret_key' ) ); ?>">
								</div>
								<div class="space-y-1">
									<label class="text-xs font-bold text-on-surface-variant uppercase">PayPal Monthly Plan ID</label>
									<input class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm" type="text" name="dlm_paypal_monthly_plan_id" value="<?php echo esc_attr( get_option( 'dlm_paypal_monthly_plan_id' ) ); ?>" placeholder="P-xxxxx">
								</div>
								<div class="space-y-1">
									<label class="text-xs font-bold text-on-surface-variant uppercase">PayPal Yearly Plan ID</label>
									<input class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm" type="text" name="dlm_paypal_yearly_plan_id" value="<?php echo esc_attr( get_option( 'dlm_paypal_yearly_plan_id' ) ); ?>" placeholder="P-xxxxx">
								</div>
								<div class="space-y-1">
									<label class="text-xs font-bold text-on-surface-variant uppercase">PayPal Lifetime Plan ID</label>
									<input class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm" type="text" name="dlm_paypal_lifetime_plan_id" value="<?php echo esc_attr( get_option( 'dlm_paypal_lifetime_plan_id' ) ); ?>" placeholder="P-xxxxx">
								</div>
							</div>
						</div>

						<?php if ( class_exists( 'WooCommerce' ) ) : ?>
						<!-- WooCommerce Configuration Panel -->
						<div id="panel-settings-woocommerce" class="space-y-6 hidden">
							<h3 class="text-lg font-bold text-on-surface border-b border-outline-variant/10 pb-3">WooCommerce Setup</h3>
							<div class="space-y-4">
								<div class="space-y-1">
									<label class="text-xs font-bold text-on-surface-variant uppercase">Monthly Plan WooCommerce Product</label>
									<select name="dlm_wc_monthly_product" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm">
										<option value=""><?php esc_html_e('— Select Product —', 'digital-library-membership' ); ?></option>
										<?php 
										$wc_products = get_posts( array( 'post_type' => 'product', 'posts_per_page' => -1 ) );
										$selected_monthly = get_option( 'dlm_wc_monthly_product' );
										foreach ( $wc_products as $prod ) {
											?>
											<option value="<?php echo intval( $prod->ID ); ?>" <?php selected( $selected_monthly, $prod->ID ); ?>><?php echo esc_html( $prod->post_title ); ?> (#<?php echo intval( $prod->ID ); ?>)</option>
											<?php
										}
										?>
									</select>
								</div>
								<div class="space-y-1">
									<label class="text-xs font-bold text-on-surface-variant uppercase">Yearly Plan WooCommerce Product</label>
									<select name="dlm_wc_yearly_product" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm">
										<option value=""><?php esc_html_e('— Select Product —', 'digital-library-membership' ); ?></option>
										<?php 
										$selected_yearly = get_option( 'dlm_wc_yearly_product' );
										foreach ( $wc_products as $prod ) {
											?>
											<option value="<?php echo intval( $prod->ID ); ?>" <?php selected( $selected_yearly, $prod->ID ); ?>><?php echo esc_html( $prod->post_title ); ?> (#<?php echo intval( $prod->ID ); ?>)</option>
											<?php
										}
										?>
									</select>
								</div>
								<div class="space-y-1">
									<label class="text-xs font-bold text-on-surface-variant uppercase">Lifetime Plan WooCommerce Product</label>
									<select name="dlm_wc_lifetime_product" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm">
										<option value=""><?php esc_html_e('— Select Product —', 'digital-library-membership' ); ?></option>
										<?php 
										$selected_lifetime = get_option( 'dlm_wc_lifetime_product' );
										foreach ( $wc_products as $prod ) {
											?>
											<option value="<?php echo intval( $prod->ID ); ?>" <?php selected( $selected_lifetime, $prod->ID ); ?>><?php echo esc_html( $prod->post_title ); ?> (#<?php echo intval( $prod->ID ); ?>)</option>
											<?php
										}
										?>
									</select>
								</div>
							</div>
						</div>
						<?php endif; ?>

						<!-- Security & Legal Panel -->
						<div id="panel-settings-security" class="space-y-6 hidden">
							<h3 class="text-lg font-bold text-on-surface border-b border-outline-variant/10 pb-3">Security & Legal Settings</h3>
							
							<div class="space-y-4">
								<h4 class="text-xs font-bold text-primary uppercase tracking-wider">Legal Pages Association</h4>
								<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
									<div class="space-y-1">
										<label class="text-xs font-bold text-on-surface-variant uppercase">Privacy Policy Page</label>
										<select name="dlm_privacy_policy_page_id" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm">
											<option value="0">-- Select Page --</option>
											<?php 
											$wp_pages = get_pages();
											$selected_privacy = get_option( 'dlm_privacy_policy_page_id' );
											foreach ( $wp_pages as $p ) {
												?>
												<option value="<?php echo intval( $p->ID ); ?>" <?php selected( $selected_privacy, $p->ID ); ?>><?php echo esc_html( $p->post_title ); ?></option>
												<?php
											}
											?>
										</select>
									</div>
									<div class="space-y-1">
										<label class="text-xs font-bold text-on-surface-variant uppercase">Terms & Conditions Page</label>
										<select name="dlm_terms_page_id" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm">
											<option value="0">-- Select Page --</option>
											<?php 
											$selected_terms = get_option( 'dlm_terms_page_id' );
											foreach ( $wp_pages as $p ) {
												?>
												<option value="<?php echo intval( $p->ID ); ?>" <?php selected( $selected_terms, $p->ID ); ?>><?php echo esc_html( $p->post_title ); ?></option>
												<?php
											}
											?>
										</select>
									</div>
								</div>

								<div class="border-t border-outline-variant/10 pt-4 mt-4"></div>

								<h4 class="text-xs font-bold text-primary uppercase tracking-wider">Google ReCAPTCHA Bot Protection</h4>
								<p class="text-xs text-secondary leading-relaxed">Protects checkout, registration, and login screens from automated attacks.</p>

								<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
									<div class="space-y-1 sm:col-span-2">
										<label class="text-xs font-bold text-on-surface-variant uppercase">ReCAPTCHA Version</label>
										<select name="dlm_recaptcha_version" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm">
											<option value="v2" <?php selected( get_option( 'dlm_recaptcha_version', 'v2' ), 'v2' ); ?>>v2 Checkbox ("I'm not a robot")</option>
											<option value="v3" <?php selected( get_option( 'dlm_recaptcha_version' ), 'v3' ); ?>>v3 Invisible</option>
										</select>
									</div>
									<div class="space-y-1">
										<label class="text-xs font-bold text-on-surface-variant uppercase">ReCAPTCHA Site Key</label>
										<input class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm" type="text" name="dlm_recaptcha_site_key" value="<?php echo esc_attr( get_option( 'dlm_recaptcha_site_key' ) ); ?>" placeholder="e.g. 6LdK...">
									</div>
									<div class="space-y-1">
										<label class="text-xs font-bold text-on-surface-variant uppercase">ReCAPTCHA Secret Key</label>
										<input class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm" type="password" name="dlm_recaptcha_secret_key" value="<?php echo esc_attr( get_option( 'dlm_recaptcha_secret_key' ) ); ?>" placeholder="e.g. 6LdK_secret...">
									</div>
								</div>
							</div>
						</div>

						<!-- Page Maintenance Tools -->
						<div class="mt-8 pt-6 border-t border-outline-variant/10">
							<h3 class="text-sm font-bold text-on-surface uppercase tracking-wider mb-2">Frontend Pages Tools</h3>
							<p class="text-xs text-secondary mb-4">If any required plugin pages (Library, Library Account, Plan, Checkout) were accidentally deleted or trashed, click below to recreate them automatically.</p>
							<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=dlm_recreate_pages' ), 'dlm_recreate_pages_nonce' ) ); ?>" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold bg-surface-container-high hover:bg-surface-container-highest border border-outline-variant/30 text-on-surface transition-all">
								<i class="fa-solid fa-arrows-rotate text-xs"></i>
								Recreate Missing Pages
							</a>
						</div>

						<div class="pt-6 border-t border-outline-variant/10 flex justify-end gap-3 mt-6">
							<button type="submit" class="px-5 py-2.5 rounded-xl font-bold text-sm bg-primary text-white hover:opacity-90 transition-all">Save Options Settings</button>
						</div>
					</form>
				</div>
			</div>
		</section>
	</main>

	<!-- Shared Mobile Bottom Navigation -->
	<nav class="md:hidden fixed bottom-0 left-0 w-full z-50 flex justify-around items-center px-4 py-3 pb-safe bg-white/90 backdrop-blur-xl border-t border-outline-variant/30 shadow-lg rounded-t-xl">
		<a class="flex flex-col items-center justify-center text-secondary transition-all cursor-pointer nav-active" data-nav="dashboard" onclick="navigateSpa('dashboard')">
			<i class="fa-solid fa-gauge-high"></i>
			<span class="text-[10px] font-bold mt-0.5">Stats</span>
		</a>
		<a class="flex flex-col items-center justify-center text-secondary transition-all cursor-pointer" data-nav="books" onclick="navigateSpa('books')">
			<i class="fa-solid fa-book"></i>
			<span class="text-[10px] font-bold mt-0.5">Books</span>
		</a>
		<a class="flex flex-col items-center justify-center text-secondary transition-all cursor-pointer" data-nav="members" onclick="navigateSpa('members')">
			<i class="fa-solid fa-users"></i>
			<span class="text-[10px] font-bold mt-0.5">Users</span>
		</a>
		<a class="flex flex-col items-center justify-center text-secondary transition-all cursor-pointer relative" data-nav="transactions" onclick="navigateSpa('transactions')">
			<i class="fa-solid fa-receipt"></i>
			<span class="text-[10px] font-bold mt-0.5">Orders</span>
			<?php if ( $pending_tx > 0 ) : ?>
				<span class="absolute top-1 right-2 w-2 h-2 bg-error rounded-full"></span>
			<?php endif; ?>
		</a>
		<a class="flex flex-col items-center justify-center text-secondary transition-all cursor-pointer" data-nav="analytics" onclick="navigateSpa('analytics')">
			<i class="fa-solid fa-chart-line"></i>
			<span class="text-[10px] font-bold mt-0.5">Sales</span>
		</a>
		<a class="flex flex-col items-center justify-center text-secondary transition-all cursor-pointer" data-nav="settings" onclick="navigateSpa('settings')">
			<i class="fa-solid fa-gear"></i>
			<span class="text-[10px] font-bold mt-0.5">Settings</span>
		</a>
	</nav>

	<!-- MODALS -->

	<!-- Add Book Modal -->
	<div id="add-book-modal" class="fixed inset-0 z-[1000] items-center justify-center p-4 hidden">
		<div class="absolute inset-0 modal-backdrop" data-close-modal="add-book-modal"></div>
		<div class="relative bg-white w-full max-w-lg rounded-3xl shadow-xl overflow-hidden border border-outline-variant/20 animate-in fade-in zoom-in duration-200 z-10">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
				<input type="hidden" name="action" value="dlm_save_book">
				<?php wp_nonce_field( 'dlm_save_book_nonce', 'dlm_nonce' ); ?>
				
				<div class="px-8 py-6 border-b border-outline-variant/10 flex justify-between items-center bg-surface-container-low/30">
					<h3 class="text-lg font-bold text-on-surface">Add Book</h3>
					<button type="button" data-close-modal="add-book-modal" class="p-1.5 hover:bg-surface-container-high/50 rounded-full transition-colors"><i class="fa-solid fa-xmark"></i></button>
				</div>
				
				<div class="p-8 space-y-4 max-h-[60vh] overflow-y-auto">
					<div class="space-y-1">
						<label class="text-xs font-bold text-on-surface-variant uppercase">Book Title *</label>
						<input name="title" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm" placeholder="e.g. The Quiet Forest" type="text" required>
					</div>
					<div class="space-y-1">
						<label class="text-xs font-bold text-on-surface-variant uppercase">Author Name</label>
						<input name="author" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm" placeholder="e.g. Liam Sterling" type="text">
					</div>
					<div class="space-y-1">
						<label class="text-xs font-bold text-on-surface-variant uppercase">Description</label>
						<textarea name="description" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm resize-none" rows="3" placeholder="Description of the book..."></textarea>
					</div>
					<div class="space-y-1">
						<label class="text-xs font-bold text-on-surface-variant uppercase">Book Category</label>
						<select name="book_category" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm">
							<option value=""><?php esc_html_e('— None —', 'digital-library-membership' ); ?></option>
							<?php 
							$categories = get_terms( array( 'taxonomy' => 'dlm_book_category', 'hide_empty' => false ) );
							if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) {
								foreach ( $categories as $cat ) {
									?>
									<option value="<?php echo intval( $cat->term_id ); ?>"><?php echo esc_html( $cat->name ); ?></option>
									<?php
								}
							}
							?>
						</select>
					</div>
					<div class="space-y-1">
						<label class="text-xs font-bold text-on-surface-variant uppercase">Book Tags (comma separated)</label>
						<input name="book_tags" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm" placeholder="e.g. classic, fiction, history" type="text">
					</div>
					<div class="space-y-1">
						<label class="text-xs font-bold text-on-surface-variant uppercase">Book Document File * (.pdf Only)</label>
						<div class="relative flex flex-col items-center justify-center border-2 border-dashed border-outline-variant/30 rounded-2xl p-6 bg-surface-container-low/20 hover:border-primary/50 transition-colors group cursor-pointer h-32">
							<input type="file" name="book_file" accept=".pdf" class="absolute inset-0 opacity-0 cursor-pointer dlm-file-input" required>
							<div class="text-center space-y-2 pointer-events-none">
								<i class="fa-solid fa-file-pdf text-3xl text-secondary/40 group-hover:text-primary/70 transition-colors"></i>
								<p class="text-xs font-semibold text-on-surface select-file-label">Drag & Drop or Click to upload book</p>
								<p class="text-[10px] text-secondary">Only PDF format is allowed for book uploads. (max 50MB)</p>
							</div>
						</div>
					</div>
					<div class="space-y-1">
						<label class="text-xs font-bold text-on-surface-variant uppercase">Book Cover Image</label>
						<div class="flex items-center gap-4">
							<div class="w-14 h-20 bg-surface-container rounded-lg border border-outline-variant/20 flex items-center justify-center text-secondary/30 overflow-hidden shrink-0">
								<img id="add-cover-preview" class="w-full h-full object-cover hidden" alt="Cover Preview">
								<i id="add-cover-placeholder" class="fa-regular fa-image text-2xl"></i>
							</div>
							<div class="flex-grow flex gap-2">
								<input type="text" name="cover_image_url" id="add-book-cover-input" class="w-full px-4 py-2 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm" placeholder="Cover Image URL">
								<button type="button" id="add-book-select-cover-btn" class="bg-surface-container-high px-4 py-2 rounded-xl text-xs font-bold hover:bg-surface-container-highest border border-outline-variant/30 shrink-0">Select</button>
							</div>
						</div>
					</div>
					<div class="space-y-1">
						<label class="text-xs font-bold text-on-surface-variant uppercase">Initial Status</label>
						<select name="status" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm">
							<option value="publish">Published</option>
							<option value="draft">Draft</option>
						</select>
					</div>
				</div>
				
				<div class="px-8 py-5 border-t border-outline-variant/10 bg-surface-container-low/30 flex justify-end gap-3">
					<button type="button" data-close-modal="add-book-modal" class="px-5 py-2.5 rounded-xl font-bold text-sm text-secondary hover:bg-secondary-container/30 transition-all">Cancel</button>
					<button type="submit" class="px-5 py-2.5 rounded-xl font-bold text-sm bg-primary text-white hover:opacity-90">Upload Book</button>
				</div>
			</form>
		</div>
	</div>

	<!-- Edit Book Modal -->
	<div id="edit-book-modal" class="fixed inset-0 z-[1000] items-center justify-center p-4 hidden">
		<div class="absolute inset-0 modal-backdrop" data-close-modal="edit-book-modal"></div>
		<div class="relative bg-white w-full max-w-lg rounded-3xl shadow-xl overflow-hidden border border-outline-variant/20 animate-in fade-in zoom-in duration-200 z-10">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
				<input type="hidden" name="action" value="dlm_edit_book">
				<input type="hidden" name="book_id" id="edit-book-id">
				<?php wp_nonce_field( 'dlm_edit_book_nonce', 'dlm_nonce' ); ?>
				
				<div class="px-8 py-6 border-b border-outline-variant/10 flex justify-between items-center bg-surface-container-low/30">
					<h3 class="text-lg font-bold text-on-surface">Edit Book Details</h3>
					<button type="button" data-close-modal="edit-book-modal" class="p-1.5 hover:bg-surface-container-high/50 rounded-full transition-colors"><i class="fa-solid fa-xmark"></i></button>
				</div>
				
				<div class="p-8 space-y-4 max-h-[60vh] overflow-y-auto">
					<div class="space-y-1">
						<label class="text-xs font-bold text-on-surface-variant uppercase">Book Title *</label>
						<input name="title" id="edit-book-title" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm" type="text" required>
					</div>
					<div class="space-y-1">
						<label class="text-xs font-bold text-on-surface-variant uppercase">Author Name</label>
						<input name="author" id="edit-book-author" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm" type="text">
					</div>
					<div class="space-y-1">
						<label class="text-xs font-bold text-on-surface-variant uppercase">Description</label>
						<textarea name="description" id="edit-book-description" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm resize-none" rows="3"></textarea>
					</div>
					<div class="space-y-1">
						<label class="text-xs font-bold text-on-surface-variant uppercase">Book Category</label>
						<select name="book_category" id="edit-book-category" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm">
							<option value=""><?php esc_html_e('— None —', 'digital-library-membership' ); ?></option>
							<?php 
							if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) {
								foreach ( $categories as $cat ) {
									?>
									<option value="<?php echo intval( $cat->term_id ); ?>"><?php echo esc_html( $cat->name ); ?></option>
									<?php
								}
							}
							?>
						</select>
					</div>
					<div class="space-y-1">
						<label class="text-xs font-bold text-on-surface-variant uppercase">Book Tags (comma separated)</label>
						<input name="book_tags" id="edit-book-tags" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm" placeholder="e.g. classic, fiction, history" type="text">
					</div>
					<div class="space-y-1">
						<label class="text-xs font-bold text-on-surface-variant uppercase">Book Document File (Leave empty to keep existing)</label>
						<div class="relative flex flex-col items-center justify-center border-2 border-dashed border-outline-variant/30 rounded-2xl p-6 bg-surface-container-low/20 hover:border-primary/50 transition-colors group cursor-pointer h-32">
							<input type="file" name="book_file" accept=".pdf" class="absolute inset-0 opacity-0 cursor-pointer dlm-file-input">
							<div class="text-center space-y-2 pointer-events-none">
								<i class="fa-solid fa-file-pdf text-3xl text-secondary/40 group-hover:text-primary/70 transition-colors"></i>
								<p class="text-xs font-semibold text-on-surface select-file-label">Drag & Drop or Click to upload new file</p>
								<p class="text-[10px] text-secondary">Only PDF format is allowed for book uploads. (max 50MB)</p>
							</div>
						</div>
					</div>
					<div class="space-y-1">
						<label class="text-xs font-bold text-on-surface-variant uppercase">Book Cover Image</label>
						<div class="flex items-center gap-4">
							<div class="w-14 h-20 bg-surface-container rounded-lg border border-outline-variant/20 flex items-center justify-center text-secondary/30 overflow-hidden shrink-0">
								<img id="edit-cover-preview" class="w-full h-full object-cover hidden" alt="Cover Preview">
								<i id="edit-cover-placeholder" class="fa-regular fa-image text-2xl"></i>
							</div>
							<div class="flex-grow flex gap-2">
								<input type="text" name="cover_image_url" id="edit-book-cover-input" class="w-full px-4 py-2 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm" placeholder="Cover Image URL">
								<button type="button" id="edit-book-select-cover-btn" class="bg-surface-container-high px-4 py-2 rounded-xl text-xs font-bold hover:bg-surface-container-highest border border-outline-variant/30 shrink-0">Select</button>
							</div>
						</div>
					</div>
					<div class="space-y-1">
						<label class="text-xs font-bold text-on-surface-variant uppercase">Status</label>
						<select name="status" id="edit-book-status" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm">
							<option value="publish">Published</option>
							<option value="draft">Draft</option>
						</select>
					</div>
				</div>
				
				<div class="px-8 py-5 border-t border-outline-variant/10 bg-surface-container-low/30 flex justify-end gap-3">
					<button type="button" data-close-modal="edit-book-modal" class="px-5 py-2.5 rounded-xl font-bold text-sm text-secondary hover:bg-secondary-container/30 transition-all">Cancel</button>
					<button type="submit" class="px-5 py-2.5 rounded-xl font-bold text-sm bg-primary text-white hover:opacity-90">Save Changes</button>
				</div>
			</form>
		</div>
	</div>

	<!-- Delete Book Modal -->
	<div id="delete-book-modal" class="fixed inset-0 z-[1000] items-center justify-center p-4 hidden">
		<div class="absolute inset-0 modal-backdrop" data-close-modal="delete-book-modal"></div>
		<div class="relative bg-white w-full max-w-md rounded-3xl shadow-xl overflow-hidden border border-outline-variant/20 animate-in fade-in zoom-in duration-200 z-10">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="dlm_delete_book">
				<input type="hidden" name="book_id" id="delete-book-id">
				<?php wp_nonce_field( 'dlm_delete_book_nonce', 'dlm_nonce' ); ?>
				
				<div class="p-8">
					<div class="flex items-center gap-4 mb-6">
						<div class="w-12 h-12 rounded-full bg-error-container/30 flex items-center justify-center text-error">
							<i class="fa-solid fa-trash-can"></i>
						</div>
						<h3 class="text-lg font-bold text-on-surface">Delete Book</h3>
					</div>
					<p class="text-sm text-on-surface-variant leading-relaxed mb-8">Are you sure you want to delete <span class="font-bold text-on-surface" id="delete-book-title-display">this book</span>? This action is permanent and cannot be undone.</p>
					<div class="flex flex-col sm:flex-row gap-3 justify-end">
						<button type="button" data-close-modal="delete-book-modal" class="px-6 py-3 rounded-xl font-bold text-sm text-secondary hover:bg-secondary-container/30 transition-all">Cancel</button>
						<button type="submit" class="px-6 py-3 rounded-xl font-bold text-sm bg-error text-white hover:shadow-lg">Delete Book</button>
					</div>
				</div>
			</form>
		</div>
	</div>

	<!-- Send Email Modal -->
	<div id="send-email-modal" class="fixed inset-0 z-[1000] items-center justify-center p-4 hidden">
		<div class="absolute inset-0 modal-backdrop" data-close-modal="send-email-modal"></div>
		<div class="relative bg-white w-full max-w-lg rounded-3xl shadow-xl overflow-hidden border border-outline-variant/20 animate-in fade-in zoom-in duration-200 z-10">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="dlm_send_member_email">
				<input type="hidden" name="email_recipient" id="send-email-recipient-input">
				<?php wp_nonce_field( 'dlm_send_email_nonce', 'dlm_nonce' ); ?>

				<div class="px-8 py-6 border-b border-outline-variant/10 flex justify-between items-center bg-surface-container-low/30">
					<h3 class="text-lg font-bold text-on-surface">Send Direct Email</h3>
					<button type="button" data-close-modal="send-email-modal" class="p-1.5 hover:bg-surface-container-high/50 rounded-full transition-colors"><i class="fa-solid fa-xmark"></i></button>
				</div>
				<div class="p-8 space-y-4">
					<div class="space-y-1">
						<label class="text-xs font-bold text-on-surface-variant uppercase">To</label>
						<input id="send-email-recipient-display" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 text-sm bg-slate-50 text-secondary cursor-not-allowed" type="text" disabled>
					</div>
					<div class="space-y-1">
						<label class="text-xs font-bold text-on-surface-variant uppercase">Email Subject</label>
						<input name="email_subject" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm" placeholder="e.g. Subscription Renewal Notification" type="text" required>
					</div>
					<div class="space-y-1">
						<label class="text-xs font-bold text-on-surface-variant uppercase">Message Content</label>
						<textarea name="email_message" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm resize-none" rows="6" placeholder="Type message details..." required></textarea>
					</div>
				</div>
				<div class="px-8 py-5 border-t border-outline-variant/10 bg-surface-container-low/30 flex justify-end gap-3">
					<button type="button" data-close-modal="send-email-modal" class="px-5 py-2.5 rounded-xl font-bold text-sm text-secondary hover:bg-secondary-container/30 transition-all">Cancel</button>
					<button type="submit" class="px-5 py-2.5 rounded-xl font-bold text-sm bg-primary text-white hover:opacity-90 flex items-center gap-2">
						<i class="fa-regular fa-paper-plane text-sm"></i>
						Send Email
					</button>
				</div>
			</form>
		</div>
	</div>

	<!-- Edit Member / Override Modal -->
	<div id="edit-member-modal" class="fixed inset-0 z-[1000] items-center justify-center p-4 hidden">
		<div class="absolute inset-0 modal-backdrop" data-close-modal="edit-member-modal"></div>
		<div class="relative bg-white w-full max-w-lg rounded-3xl shadow-xl overflow-hidden border border-outline-variant/20 animate-in fade-in zoom-in duration-200 z-10">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="dlm_member_override">
				<input type="hidden" name="user_email" id="edit-member-email-hidden">
				<?php wp_nonce_field( 'dlm_member_override_nonce', 'dlm_nonce' ); ?>

				<div class="px-8 py-6 border-b border-outline-variant/10 flex justify-between items-center bg-surface-container-low/30">
					<h3 class="text-lg font-bold text-on-surface">Edit Member Override</h3>
					<button type="button" data-close-modal="edit-member-modal" class="p-1.5 hover:bg-surface-container-high/50 rounded-full transition-colors"><i class="fa-solid fa-xmark"></i></button>
				</div>
				<div class="p-8 space-y-4">
					<div class="space-y-1">
						<label class="text-xs font-bold text-on-surface-variant uppercase">Full Name</label>
						<input name="display_name" id="edit-member-name" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 text-sm focus:border-primary focus:ring-0" type="text" required>
					</div>
					<div class="space-y-1">
						<label class="text-xs font-bold text-on-surface-variant uppercase">Email</label>
						<input id="edit-member-email" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 text-sm bg-slate-50 text-secondary cursor-not-allowed" type="email" disabled>
					</div>
					<div class="space-y-1">
						<label class="text-xs font-bold text-on-surface-variant uppercase">Override Access Status</label>
						<select name="override_status" id="edit-member-override-status" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm">
							<option value="active">Active</option>
							<option value="disabled">Disabled</option>
						</select>
					</div>
					
					<!-- Conditional override active plan details fields -->
					<div class="edit-override-active-fields space-y-4 hidden" style="display: none;">
						<div class="space-y-1">
							<label class="text-xs font-bold text-on-surface-variant uppercase">Billing Cycle / Tier</label>
							<select name="plan_interval" id="edit-member-plan-interval" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm">
								<option value="monthly">Monthly Plan</option>
								<option value="yearly">Yearly Plan</option>
								<option value="lifetime">Lifetime Access</option>
							</select>
						</div>
						<div class="space-y-1">
							<label class="text-xs font-bold text-on-surface-variant uppercase">Custom Expiry Date (Optional)</label>
							<input name="expires_at" id="edit-member-expires-at" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 text-sm focus:border-primary focus:ring-0" type="date">
							<p class="text-[10px] text-secondary">Leave empty to auto-calculate based on plan interval.</p>
						</div>
					</div>
				</div>
				<div class="px-8 py-5 border-t border-outline-variant/10 bg-surface-container-low/30 flex justify-end gap-3">
					<button type="button" data-close-modal="edit-member-modal" class="px-5 py-2.5 rounded-xl font-bold text-sm text-secondary hover:bg-secondary-container/30 transition-all">Cancel</button>
					<button type="submit" class="px-5 py-2.5 rounded-xl font-bold text-sm bg-primary text-white hover:opacity-90">Save Changes</button>
				</div>
			</form>
		</div>
	</div>

	<!-- Add Member Modal -->
	<div id="add-member-modal" class="fixed inset-0 z-[1000] items-center justify-center p-4 hidden">
		<div class="absolute inset-0 modal-backdrop" data-close-modal="add-member-modal"></div>
		<div class="relative bg-white w-full max-w-lg rounded-3xl shadow-xl overflow-hidden border border-outline-variant/20 animate-in fade-in zoom-in duration-200 z-10">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="dlm_add_member">
				<?php wp_nonce_field( 'dlm_add_member_nonce', 'dlm_nonce' ); ?>

				<div class="px-8 py-6 border-b border-outline-variant/10 flex justify-between items-center bg-surface-container-low/30">
					<h3 class="text-lg font-bold text-on-surface">Add New Member</h3>
					<button type="button" data-close-modal="add-member-modal" class="p-1.5 hover:bg-surface-container-high/50 rounded-full transition-colors"><i class="fa-solid fa-xmark"></i></button>
				</div>
				<div class="p-8 space-y-4">
					<div class="space-y-1">
						<label class="text-xs font-bold text-on-surface-variant uppercase">Full Display Name *</label>
						<input name="display_name" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 text-sm focus:border-primary focus:ring-0" type="text" placeholder="e.g. John Doe" required>
					</div>
					<div class="space-y-1">
						<label class="text-xs font-bold text-on-surface-variant uppercase">Email Address *</label>
						<input name="user_email" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 text-sm focus:border-primary focus:ring-0" type="email" placeholder="e.g. john@example.com" required>
					</div>
					<div class="space-y-1">
						<label class="text-xs font-bold text-on-surface-variant uppercase">Password *</label>
						<input name="user_pass" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 text-sm focus:border-primary focus:ring-0" type="password" placeholder="Min 6 characters" required minlength="6">
					</div>
					<div class="space-y-1">
						<label class="text-xs font-bold text-on-surface-variant uppercase">Confirm Password *</label>
						<input name="user_pass_confirm" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 text-sm focus:border-primary focus:ring-0" type="password" placeholder="Repeat password" required minlength="6">
					</div>
					<div class="space-y-1">
						<label class="text-xs font-bold text-on-surface-variant uppercase">Billing Cycle / Tier</label>
						<select name="plan_interval" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm">
							<option value="monthly">Monthly Plan</option>
							<option value="yearly">Yearly Plan</option>
							<option value="lifetime">Lifetime Access</option>
						</select>
					</div>
				</div>
				<div class="px-8 py-5 border-t border-outline-variant/10 bg-surface-container-low/30 flex justify-end gap-3">
					<button type="button" data-close-modal="add-member-modal" class="px-5 py-2.5 rounded-xl font-bold text-sm text-secondary hover:bg-secondary-container/30 transition-all">Cancel</button>
					<button type="submit" class="px-5 py-2.5 rounded-xl font-bold text-sm bg-primary text-white hover:opacity-90">Add Member</button>
				</div>
			</form>
		</div>
	</div>

	<!-- Delete Member Modal -->
	<div id="delete-member-modal" class="fixed inset-0 z-[1000] items-center justify-center p-4 hidden">
		<div class="absolute inset-0 modal-backdrop" data-close-modal="delete-member-modal"></div>
		<div class="relative bg-white w-full max-w-md rounded-3xl shadow-xl overflow-hidden border border-outline-variant/20 animate-in fade-in zoom-in duration-200 z-10">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="dlm_delete_subscription">
				<input type="hidden" name="subscription_db_id" id="delete-member-db-id">
				<input type="hidden" name="user_id" id="delete-member-user-id">
				<?php wp_nonce_field( 'dlm_delete_subscription_nonce', 'dlm_nonce' ); ?>

				<div class="p-8">
					<div class="flex items-center gap-4 mb-6">
						<div class="w-12 h-12 rounded-full bg-error-container/30 flex items-center justify-center text-error">
							<i class="fa-solid fa-user-minus"></i>
						</div>
						<h3 class="text-lg font-bold text-on-surface">Delete Member Record</h3>
					</div>
					<p class="text-sm text-on-surface-variant leading-relaxed mb-8">Are you sure you want to delete <span class="font-bold text-on-surface" id="delete-member-name-display">this member</span>? Their subscription history record in the database will be deleted.</p>
					<div class="flex flex-col sm:flex-row gap-3 justify-end">
						<button type="button" data-close-modal="delete-member-modal" class="px-6 py-3 rounded-xl font-bold text-sm text-secondary hover:bg-secondary-container/30 transition-all">Cancel</button>
						<button type="submit" class="px-6 py-3 rounded-xl font-bold text-sm bg-error text-white hover:shadow-lg">Delete Member</button>
					</div>
				</div>
			</form>
		</div>
	</div>
	<!-- Add Transaction Modal -->
	<div id="add-transaction-modal" class="fixed inset-0 z-[1000] items-center justify-center p-4 hidden">
		<div class="absolute inset-0 modal-backdrop" data-close-modal="add-transaction-modal"></div>
		<div class="relative bg-white w-full max-w-lg rounded-3xl shadow-xl overflow-hidden border border-outline-variant/20 animate-in fade-in zoom-in duration-200 z-10">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="dlm_save_transaction">
				<?php wp_nonce_field( 'dlm_save_transaction_nonce', 'dlm_nonce' ); ?>

				<div class="px-8 py-6 border-b border-outline-variant/10 flex justify-between items-center bg-surface-container-low/30">
					<h3 class="text-lg font-bold text-on-surface">Add Transaction</h3>
					<button type="button" data-close-modal="add-transaction-modal" class="p-1.5 hover:bg-surface-container-high/50 rounded-full transition-colors"><i class="fa-solid fa-xmark"></i></button>
				</div>
				<div class="p-8 space-y-4">
					<div class="space-y-1">
						<label class="text-xs font-bold text-on-surface-variant uppercase">Select User *</label>
						<select name="user_id" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm" required>
							<option value=""><?php esc_html_e('— Select User —', 'digital-library-membership' ); ?></option>
							<?php 
							$all_users = get_users( array( 'orderby' => 'display_name' ) );
							foreach ( $all_users as $u ) {
								?>
								<option value="<?php echo intval( $u->ID ); ?>"><?php echo esc_html( $u->display_name ); ?> (<?php echo esc_html( $u->user_email ); ?>)</option>
								<?php
							}
							?>
						</select>
					</div>
					<div class="grid grid-cols-2 gap-4">
						<div class="space-y-1">
							<label class="text-xs font-bold text-on-surface-variant uppercase">Subscription ID</label>
							<input name="subscription_id" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 text-sm focus:border-primary focus:ring-0" type="text" placeholder="e.g. MANUAL-1234">
						</div>
						<div class="space-y-1">
							<label class="text-xs font-bold text-on-surface-variant uppercase">Transaction Reference ID *</label>
							<input name="transaction_id" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 text-sm focus:border-primary focus:ring-0" type="text" placeholder="e.g. TXN-5566" required>
						</div>
					</div>
					<div class="grid grid-cols-3 gap-4">
						<div class="space-y-1">
							<label class="text-xs font-bold text-on-surface-variant uppercase">Gateway / Provider</label>
							<select name="provider" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm">
								<option value="manual">Manual Bank</option>
								<option value="stripe">Stripe</option>
								<option value="paypal">PayPal</option>
								<option value="woocommerce">WooCommerce</option>
							</select>
						</div>
						<div class="space-y-1">
							<label class="text-xs font-bold text-on-surface-variant uppercase">Amount</label>
							<input name="amount" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 text-sm focus:border-primary focus:ring-0" type="number" step="0.01" value="0.00" required>
						</div>
						<div class="space-y-1">
							<label class="text-xs font-bold text-on-surface-variant uppercase">Currency</label>
							<input name="currency" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 text-sm focus:border-primary focus:ring-0" type="text" value="<?php echo esc_attr( $currency ); ?>" required>
						</div>
					</div>
					<div class="space-y-1">
						<label class="text-xs font-bold text-on-surface-variant uppercase">Status</label>
						<select name="status" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm">
							<option value="waiting_approval">Waiting Approval</option>
							<option value="completed">Approved / Completed</option>
							<option value="refunded">Refunded</option>
						</select>
					</div>
				</div>
				<div class="px-8 py-5 border-t border-outline-variant/10 bg-surface-container-low/30 flex justify-end gap-3">
					<button type="button" data-close-modal="add-transaction-modal" class="px-5 py-2.5 rounded-xl font-bold text-sm text-secondary hover:bg-secondary-container/30 transition-all">Cancel</button>
					<button type="submit" class="px-5 py-2.5 rounded-xl font-bold text-sm bg-primary text-white hover:opacity-90">Add Transaction</button>
				</div>
			</form>
		</div>
	</div>

	<!-- Edit Transaction Modal -->
	<div id="edit-transaction-modal" class="fixed inset-0 z-[1000] items-center justify-center p-4 hidden">
		<div class="absolute inset-0 modal-backdrop" data-close-modal="edit-transaction-modal"></div>
		<div class="relative bg-white w-full max-w-lg rounded-3xl shadow-xl overflow-hidden border border-outline-variant/20 animate-in fade-in zoom-in duration-200 z-10">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="dlm_edit_transaction">
				<input type="hidden" name="id" id="edit-tx-db-id">
				<?php wp_nonce_field( 'dlm_edit_transaction_nonce', 'dlm_nonce' ); ?>

				<div class="px-8 py-6 border-b border-outline-variant/10 flex justify-between items-center bg-surface-container-low/30">
					<h3 class="text-lg font-bold text-on-surface">Edit Transaction Details</h3>
					<button type="button" data-close-modal="edit-transaction-modal" class="p-1.5 hover:bg-surface-container-high/50 rounded-full transition-colors"><i class="fa-solid fa-xmark"></i></button>
				</div>
				<div class="p-8 space-y-4">
					<div class="space-y-1">
						<label class="text-xs font-bold text-on-surface-variant uppercase">User Details</label>
						<input id="edit-tx-user-display" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 text-sm bg-slate-50 text-secondary cursor-not-allowed" type="text" disabled>
					</div>
					<div class="grid grid-cols-2 gap-4">
						<div class="space-y-1">
							<label class="text-xs font-bold text-on-surface-variant uppercase">Subscription ID</label>
							<input id="edit-tx-sub-display" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 text-sm bg-slate-50 text-secondary cursor-not-allowed" type="text" disabled>
						</div>
						<div class="space-y-1">
							<label class="text-xs font-bold text-on-surface-variant uppercase">Transaction Reference ID</label>
							<input id="edit-tx-ref-display" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 text-sm bg-slate-50 text-secondary cursor-not-allowed" type="text" disabled>
						</div>
					</div>
					<div class="grid grid-cols-3 gap-4">
						<div class="space-y-1">
							<label class="text-xs font-bold text-on-surface-variant uppercase">Gateway / Provider</label>
							<input id="edit-tx-provider-display" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 text-sm bg-slate-50 text-secondary cursor-not-allowed" type="text" disabled>
						</div>
						<div class="space-y-1">
							<label class="text-xs font-bold text-on-surface-variant uppercase">Amount</label>
							<input id="edit-tx-amount-display" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 text-sm bg-slate-50 text-secondary cursor-not-allowed" type="text" disabled>
						</div>
						<div class="space-y-1">
							<label class="text-xs font-bold text-on-surface-variant uppercase">Currency</label>
							<input id="edit-tx-currency-display" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 text-sm bg-slate-50 text-secondary cursor-not-allowed" type="text" disabled>
						</div>
					</div>
					<div class="space-y-1">
						<label class="text-xs font-bold text-on-surface-variant uppercase">Status</label>
						<select name="status" id="edit-tx-status" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm">
							<option value="waiting_approval">Waiting Approval</option>
							<option value="completed">Approved / Completed</option>
							<option value="refunded">Refunded</option>
						</select>
					</div>
				</div>
				<div class="px-8 py-5 border-t border-outline-variant/10 bg-surface-container-low/30 flex justify-end gap-3">
					<button type="button" data-close-modal="edit-transaction-modal" class="px-5 py-2.5 rounded-xl font-bold text-sm text-secondary hover:bg-secondary-container/30 transition-all">Cancel</button>
					<button type="submit" class="px-5 py-2.5 rounded-xl font-bold text-sm bg-primary text-white hover:opacity-90">Save Changes</button>
				</div>
			</form>
		</div>
	</div>
</div>

	<!-- Global Alert Popup Modal -->
	<div id="dlmAlertModal" class="fixed inset-0 z-50 items-center justify-center bg-black/40 backdrop-blur-sm hidden" style="align-items: center; justify-content: center;">
		<div class="bg-white rounded-3xl p-8 max-w-sm w-full mx-4 shadow-2xl border border-outline-variant/10 text-center space-y-4 relative animate-scaleUp">
			<!-- Close Button (X) -->
			<button onclick="closeAlertModal()" class="absolute top-4 right-4 p-1.5 hover:bg-surface-container-high/50 rounded-full transition-colors text-secondary hover:text-on-surface">
				<i class="fa-solid fa-xmark text-lg"></i>
			</button>
			
			<div id="dlmAlertIcon" class="w-16 h-16 mx-auto rounded-full flex items-center justify-center text-3xl">
				<!-- Icon injected by JS -->
			</div>
			
			<h4 id="dlmAlertTitle" class="text-xl font-bold text-on-surface"></h4>
			<p id="dlmAlertMessage" class="text-sm text-secondary leading-relaxed"></p>
			
			<div class="pt-2">
				<button onclick="closeAlertModal()" class="w-full bg-primary text-white py-3 rounded-xl font-semibold text-sm hover:shadow-lg active:scale-95 transition-all">OK</button>
			</div>
		</div>
	</div>

<!-- Dynamic data variables injected into JS scripts -->
<script>
window.dlmAnalyticsData = {
	currency: <?php echo json_encode( $currency ); ?>,
	totalSales: <?php echo floatval( $summary['total_sales'] ); ?>,
	activeSubscribers: <?php echo intval( $summary['active_subscribers'] ); ?>,
	totalSubscribers: <?php echo intval( $summary['total_subscribers'] ); ?>,
	mrr: <?php echo floatval( $summary['mrr'] ); ?>,
	transactions: <?php
		$txs_formatted = array();
		if ( ! empty( $summary['completed_transactions'] ) ) {
			foreach ( $summary['completed_transactions'] as $tx ) {
				$txs_formatted[] = array(
					'amount'         => floatval( $tx->amount ),
					'created_at'     => $tx->created_at,
				);
			}
		}
		echo json_encode( $txs_formatted );
	?>,
	popularBooks: <?php
		$pop_labels = array();
		$pop_values = array();
		if ( ! empty( $summary['popular_books'] ) ) {
			foreach ( $summary['popular_books'] as $pop ) {
				$pop_labels[] = $pop->title;
				$pop_values[] = intval( $pop->opens );
			}
		}
		echo json_encode( array( 'labels' => $pop_labels, 'values' => $pop_values ) );
	?>
};
</script>

