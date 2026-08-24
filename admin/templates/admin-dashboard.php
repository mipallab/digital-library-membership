<?php
/**
 * Admin Dashboard View Template
 *
 * @package DLM
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals

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
			<a class="flex items-center gap-3 px-4 py-3 text-secondary hover:bg-surface-container-low/50 hover:text-on-surface transition-all rounded-lg cursor-pointer" data-nav="plans" onclick="navigateSpa('plans')">
				<i class="fa-solid fa-layer-group shrink-0"></i>
				<span class="text-sm font-semibold sidebar-text">Plans & Packages</span>
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
			<a class="flex items-center gap-3 px-4 py-3 text-secondary hover:bg-surface-container-low/50 hover:text-on-surface transition-all rounded-lg cursor-pointer" data-nav="purchases" onclick="navigateSpa('purchases')">
				<i class="fa-solid fa-bag-shopping shrink-0"></i>
				<span class="text-sm font-semibold sidebar-text">Purchases & Access</span>
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
								<th class="px-6 py-5 text-[11px] font-bold text-on-surface-variant uppercase tracking-widest">Access Model</th>
								<th class="px-6 py-5 text-[11px] font-bold text-on-surface-variant uppercase tracking-widest">Type</th>
								<th class="px-6 py-5 text-[11px] font-bold text-on-surface-variant uppercase tracking-widest">Status</th>
								<th class="px-6 py-5 text-[11px] font-bold text-on-surface-variant uppercase tracking-widest">Date</th>
								<th class="px-8 py-5 text-[11px] font-bold text-on-surface-variant uppercase tracking-widest text-right">Actions</th>
							</tr>
						</thead>
						<tbody class="divide-y divide-outline-variant/10">
							<?php if ( empty( $books ) ) : ?>
								<tr>
									<td colspan="7" class="px-8 py-10 text-center text-xs text-secondary italic"><?php esc_html_e('No books uploaded yet.', 'digital-library-membership' ); ?></td>
								</tr>
							<?php else : ?>
								<?php foreach ( $books as $bk ) : 
									$cats = wp_get_object_terms( $bk->id, 'dlm_book_category' );
									$tags = wp_get_object_terms( $bk->id, 'dlm_book_tag' );
									$cat_id = ( ! is_wp_error( $cats ) && ! empty( $cats ) ) ? $cats[0]->term_id : '';
									$tags_csv = ( ! is_wp_error( $tags ) && ! empty( $tags ) ) ? implode( ', ', wp_list_pluck( $tags, 'name' ) ) : '';
									$access_type = ! empty( $bk->access_type ) ? $bk->access_type : 'subscription_only';
									$price = isset( $bk->price ) ? floatval( $bk->price ) : 0.00;
									$is_future = ! empty( $bk->publish_date ) && ( strtotime( $bk->publish_date ) > current_time( 'timestamp' ) );
									$publish_date_formatted = ! empty( $bk->publish_date ) ? wp_date( 'Y-m-d\TH:i', strtotime( $bk->publish_date ) ) : '';
									$is_featured = ! empty( $bk->is_featured );
									$featured_title = ! empty( $bk->featured_title ) ? $bk->featured_title : '';
									$featured_description = ! empty( $bk->featured_description ) ? $bk->featured_description : '';
									$featured_banner_id = ! empty( $bk->featured_banner_id ) ? intval( $bk->featured_banner_id ) : 0;
									$featured_banner_url = ! empty( $bk->featured_banner_url ) ? $bk->featured_banner_url : '';
									$featured_btn1 = ! empty( $bk->featured_button_1_label ) ? $bk->featured_button_1_label : '';
									$featured_btn2 = ! empty( $bk->featured_button_2_label ) ? $bk->featured_button_2_label : '';
									$featured_order = isset( $bk->featured_order ) ? intval( $bk->featured_order ) : 0;
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
										data-access-type="<?php echo esc_attr( $access_type ); ?>"
										data-price="<?php echo esc_attr( number_format( $price, 2, '.', '' ) ); ?>"
										data-publish-date="<?php echo esc_attr( $publish_date_formatted ); ?>"
										data-is-featured="<?php echo $is_featured ? '1' : '0'; ?>"
										data-featured-title="<?php echo esc_attr( $featured_title ); ?>"
										data-featured-description="<?php echo esc_attr( $featured_description ); ?>"
										data-featured-banner-id="<?php echo esc_attr( $featured_banner_id ); ?>"
										data-featured-banner-url="<?php echo esc_url( $featured_banner_url ); ?>"
										data-featured-btn1="<?php echo esc_attr( $featured_btn1 ); ?>"
										data-featured-btn2="<?php echo esc_attr( $featured_btn2 ); ?>"
										data-featured-order="<?php echo esc_attr( $featured_order ); ?>"
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
												<div class="flex items-center gap-1.5 flex-wrap">
													<span class="font-bold text-on-surface text-body-lg mb-0.5"><?php echo esc_html( $bk->title ); ?></span>
													<?php if ( $is_featured ) : ?>
														<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-extrabold bg-amber-100 text-amber-900 border border-amber-300 shadow-xs" title="Featured in Hero Slider">
															<i class="fa-solid fa-star text-amber-600 text-[9px]"></i> Featured
														</span>
													<?php endif; ?>
												</div>
												<span class="text-sm text-on-surface-variant"><?php echo esc_html( $bk->author ?: __( 'Unknown Author', 'digital-library-membership' ) ); ?></span>
											</div>
										</td>
										<td class="px-6 py-4">
											<?php if ( $access_type === 'purchase_only' ) : ?>
												<span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
													<i class="fa-solid fa-tag mr-1 text-[9px]"></i>
													Purchase (<?php echo esc_html( number_format( $price, 2 ) . ' ' . $currency ); ?>)
												</span>
											<?php elseif ( $access_type === 'hybrid' ) : ?>
												<span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
													<i class="fa-solid fa-arrows-split-up-and-left mr-1 text-[9px]"></i>
													Hybrid (Sub / <?php echo esc_html( number_format( $price, 2 ) . ' ' . $currency ); ?>)
												</span>
											<?php else : ?>
												<span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
													<i class="fa-solid fa-id-card mr-1 text-[9px]"></i>
													Subscription Only
												</span>
											<?php endif; ?>
										</td>
										<td class="px-6 py-4">
											<span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-sky-100 text-sky-800 uppercase"><?php echo esc_html( $bk->file_type ); ?></span>
										</td>
										<td class="px-6 py-4">
											<?php if ( $is_future || $bk->status === 'future' ) : ?>
												<span class="inline-flex items-center px-3 py-1 rounded-full bg-purple-50 text-purple-700 border border-purple-200 text-xs font-bold" title="<?php echo esc_attr( $bk->publish_date ); ?>">
													<i class="fa-regular fa-clock mr-1.5 text-purple-600"></i>
													Scheduled
												</span>
											<?php elseif ( $bk->status === 'publish' ) : ?>
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
											<span class="text-sm text-on-surface-variant">
												<?php 
												if ( $is_future && ! empty( $bk->publish_date ) ) {
													echo esc_html( date_i18n( get_option( 'date_format' ) . ' H:i', strtotime( $bk->publish_date ) ) );
												} else {
													echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $bk->created_at ) ) );
												}
												?>
											</span>
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

		<!-- SECTION: PLANS & PACKAGES -->
		<section id="sec-plans" class="spa-section pt-10 px-6 md:px-12 space-y-6 max-w-[1440px] mx-auto hidden">
			<div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-4">
				<div>
					<h2 class="text-2xl font-bold text-on-surface">Subscription Packages</h2>
					<p class="text-secondary text-sm">Configure membership packages, pricing tiers, billing cycles, and feature lists.</p>
				</div>
				<div>
					<button data-open-modal="add-package-modal" class="flex items-center gap-2 bg-primary text-white px-6 py-3 rounded-xl font-semibold text-sm hover:shadow-lg transition-all active:scale-95">
						<i class="fa-solid fa-plus text-sm"></i>
						Add New Package
					</button>
				</div>
			</div>

			<!-- Packages Metrics Row -->
			<?php
			$total_pkg_count  = count( $packages );
			$active_pkg_count = 0;
			$total_pkg_subs   = 0;
			foreach ( $packages as $p_item ) {
				if ( ! isset( $p_item['status'] ) || 'active' === $p_item['status'] ) {
					$active_pkg_count++;
				}
				$total_pkg_subs += dlm_get_package_subscriber_count( $p_item['id'], isset( $p_item['interval'] ) ? $p_item['interval'] : '' );
			}
			?>
			<div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
				<div class="bento-card bg-white p-6 rounded-2xl border border-outline-variant/20 shadow-sm flex flex-col justify-between h-32">
					<div class="flex justify-between items-start">
						<p class="text-secondary text-[11px] uppercase tracking-wider font-bold">Total Packages</p>
						<i class="fa-solid fa-layer-group text-primary"></i>
					</div>
					<div class="flex items-baseline gap-2 mt-2">
						<h3 class="text-3xl font-bold text-on-surface"><?php echo intval( $total_pkg_count ); ?></h3>
					</div>
				</div>
				<div class="bento-card bg-white p-6 rounded-2xl border border-outline-variant/20 shadow-sm flex flex-col justify-between h-32">
					<div class="flex justify-between items-start">
						<p class="text-secondary text-[11px] uppercase tracking-wider font-bold">Active Plans (Public)</p>
						<i class="fa-solid fa-circle-check text-green-600"></i>
					</div>
					<div class="flex items-baseline gap-2 mt-2">
						<h3 class="text-3xl font-bold text-on-surface"><?php echo intval( $active_pkg_count ); ?></h3>
					</div>
				</div>
				<div class="bento-card bg-white p-6 rounded-2xl border border-outline-variant/20 shadow-sm flex flex-col justify-between h-32">
					<div class="flex justify-between items-start">
						<p class="text-secondary text-[11px] uppercase tracking-wider font-bold">Total Active Subscribers</p>
						<i class="fa-solid fa-users-line text-primary"></i>
					</div>
					<div class="flex items-baseline gap-2 mt-2">
						<h3 class="text-3xl font-bold text-on-surface"><?php echo intval( $total_pkg_subs ); ?></h3>
					</div>
				</div>
			</div>

			<!-- Packages Table Card -->
			<div class="bento-card bg-white rounded-2xl border border-outline-variant/20 shadow-sm overflow-hidden mb-8">
				<div class="px-8 py-6 border-b border-outline-variant/10 flex justify-between items-center">
					<h4 class="font-bold text-on-surface text-sm">Configured Subscription Plans</h4>
					<?php
					/* translators: %d: number of packages */
					$pkg_count_str = _n( '%d Package', '%d Packages', count( $packages ), 'digital-library-membership' );
					?>
					<span class="text-xs text-secondary"><?php echo esc_html( sprintf( $pkg_count_str, count( $packages ) ) ); ?></span>
				</div>

				<div class="overflow-x-auto">
					<table class="w-full text-left">
						<thead>
							<tr class="bg-surface-container-low/30">
								<th class="px-8 py-4 text-[10px] text-secondary font-bold tracking-[0.1em] uppercase">Package Name</th>
								<th class="px-8 py-4 text-[10px] text-secondary font-bold tracking-[0.1em] uppercase">Billing Cycle</th>
								<th class="px-8 py-4 text-[10px] text-secondary font-bold tracking-[0.1em] uppercase">Price</th>
								<th class="px-8 py-4 text-[10px] text-secondary font-bold tracking-[0.1em] uppercase">Active Subscribers</th>
								<th class="px-8 py-4 text-[10px] text-secondary font-bold tracking-[0.1em] uppercase">Status</th>
								<th class="px-8 py-4 text-[10px] text-secondary font-bold tracking-[0.1em] uppercase text-right">Actions</th>
							</tr>
						</thead>
						<tbody class="divide-y divide-outline-variant/10">
							<?php if ( empty( $packages ) ) : ?>
								<tr>
									<td colspan="6" class="px-8 py-10 text-center text-secondary text-sm italic">
										No subscription packages configured. Click "Add New Package" to create one.
									</td>
								</tr>
							<?php else : ?>
								<?php foreach ( $packages as $pkg_id => $pkg ) : 
									$is_pkg_active = ! isset( $pkg['status'] ) || 'active' === $pkg['status'];
									$pkg_interval  = isset( $pkg['interval'] ) ? $pkg['interval'] : 'monthly';
									$subs_count    = dlm_get_package_subscriber_count( $pkg['id'], $pkg_interval );
									$features_str  = ! empty( $pkg['features'] ) && is_array( $pkg['features'] ) ? implode( "\n", $pkg['features'] ) : '';
									
									$cycle_label   = ( 'lifetime' === $pkg_interval ) ? __( 'Lifetime', 'digital-library-membership' ) : ( ( 'yearly' === $pkg_interval ) ? __( 'Annual / Yearly', 'digital-library-membership' ) : __( 'Monthly', 'digital-library-membership' ) );
									$cycle_badge_bg = ( 'lifetime' === $pkg_interval ) ? 'bg-purple-50 text-purple-700 border-purple-200' : ( ( 'yearly' === $pkg_interval ) ? 'bg-amber-50 text-amber-800 border-amber-200' : 'bg-blue-50 text-blue-700 border-blue-200' );
								?>
									<tr class="hover:bg-surface-container-lowest transition-colors"
										data-package-id="<?php echo esc_attr( $pkg['id'] ); ?>"
										data-name="<?php echo esc_attr( $pkg['name'] ); ?>"
										data-badge="<?php echo esc_attr( isset( $pkg['badge'] ) ? $pkg['badge'] : '' ); ?>"
										data-description="<?php echo esc_attr( isset( $pkg['description'] ) ? $pkg['description'] : '' ); ?>"
										data-interval="<?php echo esc_attr( $pkg_interval ); ?>"
										data-price="<?php echo esc_attr( $pkg['price'] ); ?>"
										data-status="<?php echo esc_attr( $is_pkg_active ? 'active' : 'inactive' ); ?>"
										data-features="<?php echo esc_attr( $features_str ); ?>"
										data-subscribers="<?php echo intval( $subs_count ); ?>"
										data-stripe-price="<?php echo esc_attr( isset( $pkg['stripe_price_id'] ) ? $pkg['stripe_price_id'] : '' ); ?>"
										data-paypal-plan="<?php echo esc_attr( isset( $pkg['paypal_plan_id'] ) ? $pkg['paypal_plan_id'] : '' ); ?>"
										data-wc-product="<?php echo intval( isset( $pkg['wc_product_id'] ) ? $pkg['wc_product_id'] : 0 ); ?>">
										<td class="px-8 py-4">
											<div class="flex flex-col">
												<div class="flex items-center gap-2">
													<strong class="font-bold text-sm text-on-surface"><?php echo esc_html( $pkg['name'] ); ?></strong>
													<?php if ( ! empty( $pkg['badge'] ) ) : ?>
														<span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-primary/10 text-primary border border-primary/20"><?php echo esc_html( $pkg['badge'] ); ?></span>
													<?php endif; ?>
												</div>
												<?php if ( ! empty( $pkg['description'] ) ) : ?>
													<p class="text-xs text-secondary mt-0.5 max-w-sm truncate"><?php echo esc_html( $pkg['description'] ); ?></p>
												<?php endif; ?>
												<span class="text-[10px] text-secondary/70 font-mono mt-0.5">ID: <?php echo esc_html( $pkg['id'] ); ?></span>
											</div>
										</td>
										<td class="px-8 py-4">
											<span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold border <?php echo esc_attr( $cycle_badge_bg ); ?>">
												<?php echo esc_html( $cycle_label ); ?>
											</span>
										</td>
										<td class="px-8 py-4 font-bold text-sm text-on-surface">
											$<?php echo esc_html( number_format( floatval( $pkg['price'] ), 2 ) ); ?> <span class="text-xs font-normal text-secondary"><?php echo esc_html( $currency ); ?></span>
										</td>
										<td class="px-8 py-4">
											<span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold <?php echo $subs_count > 0 ? 'bg-primary/10 text-primary' : 'bg-slate-100 text-secondary'; ?>">
												<i class="fa-solid fa-user-check text-[10px]"></i>
												<?php echo intval( $subs_count ); ?> <?php echo esc_html( _n( 'Member', 'Members', $subs_count, 'digital-library-membership' ) ); ?>
											</span>
										</td>
										<td class="px-8 py-4">
											<div class="flex items-center gap-2">
												<?php if ( $is_pkg_active ) : ?>
													<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-green-50 text-green-700 border border-green-200">
														<span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
														Active
													</span>
												<?php else : ?>
													<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
														<span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
														Inactive
													</span>
												<?php endif; ?>

												<!-- Quick Toggle Form -->
												<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="inline-block">
													<input type="hidden" name="action" value="dlm_toggle_package_status">
													<input type="hidden" name="package_id" value="<?php echo esc_attr( $pkg['id'] ); ?>">
													<?php wp_nonce_field( 'dlm_package_action_nonce', 'dlm_nonce' ); ?>
													<button type="submit" class="text-[11px] font-semibold text-secondary hover:text-primary underline ml-1 cursor-pointer" title="<?php echo $is_pkg_active ? esc_attr__( 'Retire from frontend checkout', 'digital-library-membership' ) : esc_attr__( 'Activate for frontend checkout', 'digital-library-membership' ); ?>">
														<?php echo $is_pkg_active ? esc_html__( 'Deactivate', 'digital-library-membership' ) : esc_html__( 'Activate', 'digital-library-membership' ); ?>
													</button>
												</form>
											</div>
										</td>
										<td class="px-8 py-4 text-right">
											<div class="flex items-center justify-end gap-2">
												<button class="p-2 text-secondary hover:text-primary hover:bg-surface-container-low rounded-lg transition-colors btn-edit-package" title="Edit Package">
													<i class="fa-solid fa-pen-to-square text-base"></i>
												</button>
												<button class="p-2 text-error-red/75 hover:text-error-red hover:bg-error-container/20 rounded-lg transition-colors btn-delete-package" title="Delete Package">
													<i class="fa-solid fa-trash-can text-base"></i>
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

		<!-- SECTION 3C: BOOK PURCHASES & ACCESS -->
		<section id="sec-purchases" class="spa-section pt-10 px-6 md:px-12 space-y-6 max-w-[1440px] mx-auto hidden">
			<div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-4">
				<div>
					<h2 class="text-2xl font-bold text-on-surface">Book Purchases & Access Overview</h2>
					<p class="text-secondary text-sm">Monitor individual book purchases, access matrix states, and download grant records.</p>
				</div>
				<div class="flex flex-wrap items-center gap-3">
					<div class="flex items-center bg-white border border-outline-variant/30 rounded-xl px-4 py-2.5 flex-grow md:flex-grow-0 group focus-within:border-primary transition-all">
						<i class="fa-solid fa-magnifying-glass text-on-surface-variant mr-3 group-focus-within:text-primary"></i>
						<input id="purchases-search-input" class="bg-transparent border-none p-0 focus:ring-0 text-sm w-full md:w-56 placeholder:text-on-surface-variant/60" placeholder="Search buyer or book..." type="text">
					</div>
					<select id="purchases-filter-access" class="px-4 py-2.5 rounded-xl border border-outline-variant/30 text-xs font-semibold bg-white focus:border-primary focus:ring-0">
						<option value="all">All Access Types</option>
						<option value="purchase_only">Purchase Only</option>
						<option value="hybrid">Hybrid</option>
						<option value="subscription_only">Subscription Only</option>
					</select>
					<select id="purchases-filter-status" class="px-4 py-2.5 rounded-xl border border-outline-variant/30 text-xs font-semibold bg-white focus:border-primary focus:ring-0">
						<option value="all">All Statuses</option>
						<option value="completed">Completed / Active</option>
						<option value="refunded">Refunded / Revoked</option>
						<option value="pending">Pending</option>
					</select>
				</div>
			</div>

			<!-- Stats Row for Purchases -->
			<?php
			$total_purchases_count = ! empty( $book_purchases ) ? count( $book_purchases ) : 0;
			$total_purchases_revenue = 0.00;
			$active_access_count = 0;
			$refunded_access_count = 0;
			if ( ! empty( $book_purchases ) ) {
				foreach ( $book_purchases as $bp ) {
					if ( $bp->status === 'completed' ) {
						$total_purchases_revenue += floatval( $bp->amount );
						$active_access_count++;
					} elseif ( $bp->status === 'refunded' ) {
						$refunded_access_count++;
					}
				}
			}
			?>
			<div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
				<div class="bg-white p-6 rounded-2xl border border-outline-variant/20 shadow-sm flex items-center justify-between">
					<div>
						<p class="text-[10px] font-bold text-secondary uppercase tracking-wider mb-1">Book Purchases Revenue</p>
						<p class="text-2xl font-bold text-on-surface"><?php echo esc_html( number_format( $total_purchases_revenue, 2 ) ) . ' ' . esc_html( $currency ); ?></p>
						<span class="text-[11px] text-secondary"><?php echo esc_html( $total_purchases_count ); ?> total purchase logs</span>
					</div>
					<div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center text-primary">
						<i class="fa-solid fa-bag-shopping"></i>
					</div>
				</div>
				<div class="bg-white p-6 rounded-2xl border border-outline-variant/20 shadow-sm flex items-center justify-between">
					<div>
						<p class="text-[10px] font-bold text-secondary uppercase tracking-wider mb-1">Active Book Accesses</p>
						<p class="text-2xl font-bold text-green-700"><?php echo esc_html( $active_access_count ); ?></p>
						<span class="text-[11px] text-green-600 font-semibold">Read & Download Enabled</span>
					</div>
					<div class="w-10 h-10 rounded-full bg-green-50 flex items-center justify-center text-green-600">
						<i class="fa-solid fa-unlock-keyhole"></i>
					</div>
				</div>
				<div class="bg-white p-6 rounded-2xl border border-outline-variant/20 shadow-sm flex items-center justify-between">
					<div>
						<p class="text-[10px] font-bold text-secondary uppercase tracking-wider mb-1">Refunded / Revoked</p>
						<p class="text-2xl font-bold text-red-600"><?php echo esc_html( $refunded_access_count ); ?></p>
						<span class="text-[11px] text-red-500 font-semibold">Immediate access revocation</span>
					</div>
					<div class="w-10 h-10 rounded-full bg-red-50 flex items-center justify-center text-red-600">
						<i class="fa-solid fa-user-lock"></i>
					</div>
				</div>
			</div>

			<!-- Purchases Data Table -->
			<div class="bg-white rounded-3xl border border-outline-variant/10 shadow-sm overflow-hidden mb-8">
				<div class="overflow-x-auto">
					<table id="purchases-table" class="w-full text-left border-collapse">
						<thead>
							<tr class="border-b border-outline-variant/10 bg-surface-container-low/50">
								<th class="px-8 py-5 text-[11px] font-bold text-on-surface-variant uppercase tracking-widest">Book Item</th>
								<th class="px-8 py-5 text-[11px] font-bold text-on-surface-variant uppercase tracking-widest">Buyer Details</th>
								<th class="px-8 py-5 text-[11px] font-bold text-on-surface-variant uppercase tracking-widest">Access Model</th>
								<th class="px-8 py-5 text-[11px] font-bold text-on-surface-variant uppercase tracking-widest">Engine / Order</th>
								<th class="px-8 py-5 text-[11px] font-bold text-on-surface-variant uppercase tracking-widest">Price Paid</th>
								<th class="px-8 py-5 text-[11px] font-bold text-on-surface-variant uppercase tracking-widest">Status</th>
								<th class="px-8 py-5 text-[11px] font-bold text-on-surface-variant uppercase tracking-widest text-right">Date</th>
							</tr>
						</thead>
						<tbody class="divide-y divide-outline-variant/10">
							<?php if ( empty( $book_purchases ) ) : ?>
								<tr>
									<td colspan="7" class="px-8 py-10 text-center text-xs text-secondary italic"><?php esc_html_e('No book purchases recorded yet.', 'digital-library-membership' ); ?></td>
								</tr>
							<?php else : ?>
								<?php foreach ( $book_purchases as $bp ) : 
									$avatar = get_avatar_url( $bp->user_id );
									$status_badge_class = 'bg-amber-100 text-amber-700';
									if ( $bp->status === 'completed' ) {
										$status_badge_class = 'bg-green-100 text-green-700';
									} elseif ( $bp->status === 'refunded' ) {
										$status_badge_class = 'bg-red-100 text-red-700';
									}
								?>
									<tr class="hover:bg-surface-container-low/20 transition-colors group purchase-row"
										data-buyer="<?php echo esc_attr( strtolower( ( $bp->display_name ?: '' ) . ' ' . ( $bp->user_email ?: '' ) ) ); ?>"
										data-book-title="<?php echo esc_attr( strtolower( $bp->book_title ?: '' ) ); ?>"
										data-book-id="<?php echo intval( $bp->book_id ); ?>"
										data-access-type="<?php echo esc_attr( $bp->access_type ); ?>"
										data-status="<?php echo esc_attr( $bp->status ); ?>"
									>
										<td class="px-8 py-4">
											<div class="flex items-center gap-3">
												<div class="w-10 h-14 rounded-lg shadow-sm overflow-hidden bg-surface-variant shrink-0">
													<?php if ( ! empty( $bp->cover_image_url ) ) : ?>
														<img class="w-full h-full object-cover" src="<?php echo esc_url( $bp->cover_image_url ); ?>" alt="Cover">
													<?php else : ?>
														<div class="w-full h-full bg-slate-100 flex items-center justify-center text-[8px] text-secondary"><?php esc_html_e('No Cover', 'digital-library-membership' ); ?></div>
													<?php endif; ?>
												</div>
												<div>
													<p class="font-bold text-on-surface text-sm leading-snug">
														<?php 
														if ( ! empty( $bp->book_title ) ) {
															echo esc_html( $bp->book_title );
														} else {
															/* translators: %d: Book ID */
															printf( esc_html__( 'Book #%d', 'digital-library-membership' ), intval( $bp->book_id ) );
														}
														?>
													</p>
													<span class="text-[11px] text-secondary">ID: #<?php echo intval( $bp->book_id ); ?></span>
												</div>
											</div>
										</td>
										<td class="px-8 py-4">
											<div class="flex items-center gap-3">
												<div class="w-8 h-8 rounded-full overflow-hidden border border-outline-variant/20 shrink-0">
													<img class="w-full h-full object-cover" src="<?php echo esc_url( $avatar ); ?>" alt="Avatar">
												</div>
												<div>
													<p class="font-title-sm text-on-surface text-[14px] font-bold leading-tight">
														<?php 
														if ( ! empty( $bp->display_name ) ) {
															echo esc_html( $bp->display_name );
														} else {
															/* translators: %d: User ID */
															printf( esc_html__( 'User #%d', 'digital-library-membership' ), intval( $bp->user_id ) );
														}
														?>
													</p>
													<p class="font-body-md text-secondary text-[12px]"><?php echo esc_html( $bp->user_email ?: '—' ); ?></p>
												</div>
											</div>
										</td>
										<td class="px-8 py-4">
											<?php if ( $bp->access_type === 'purchase_only' ) : ?>
												<span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
													<i class="fa-solid fa-tag mr-1 text-[9px]"></i>
													Purchase Only
												</span>
											<?php elseif ( $bp->access_type === 'hybrid' ) : ?>
												<span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
													<i class="fa-solid fa-arrows-split-up-and-left mr-1 text-[9px]"></i>
													Hybrid
												</span>
											<?php else : ?>
												<span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
													<i class="fa-solid fa-id-card mr-1 text-[9px]"></i>
													Subscription Only
												</span>
											<?php endif; ?>
										</td>
										<td class="px-8 py-4">
											<div class="flex flex-col">
												<span class="font-bold text-xs uppercase text-on-surface"><?php echo esc_html( $bp->payment_engine ?: 'default' ); ?></span>
												<span class="text-[11px] text-secondary font-mono truncate max-w-[140px]"><?php echo esc_html( $bp->order_id ?: ( $bp->wc_order_id ? '#' . $bp->wc_order_id : '—' ) ); ?></span>
											</div>
										</td>
										<td class="px-8 py-4 font-bold text-sm text-on-surface">
											<?php echo esc_html( number_format( $bp->amount, 2 ) . ' ' . $bp->currency ); ?>
										</td>
										<td class="px-8 py-4">
											<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase <?php echo esc_attr( $status_badge_class ); ?>">
												<?php 
												if ( $bp->status === 'completed' ) {
													echo '<i class="fa-solid fa-check mr-1 text-[9px]"></i> Active Access';
												} elseif ( $bp->status === 'refunded' ) {
													echo '<i class="fa-solid fa-ban mr-1 text-[9px]"></i> Access Revoked';
												} else {
													echo esc_html( $bp->status );
												}
												?>
											</span>
										</td>
										<td class="px-8 py-4 text-sm text-secondary text-right">
											<?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' H:i', strtotime( $bp->created_at ) ) ); ?>
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
						<span><?php esc_html_e( 'Pricing & Instructions', 'digital-library-membership' ); ?></span>
					</button>
					<button onclick="switchSettingsTab('stripe')" id="tab-settings-stripe" class="w-full text-left px-5 py-3 rounded-xl font-bold text-sm text-secondary hover:bg-surface-container-low transition-all flex items-center gap-3">
						<i class="fa-solid fa-credit-card"></i>
						<span><?php esc_html_e( 'Stripe Setup', 'digital-library-membership' ); ?></span>
					</button>
					<button onclick="switchSettingsTab('paypal')" id="tab-settings-paypal" class="w-full text-left px-5 py-3 rounded-xl font-bold text-sm text-secondary hover:bg-surface-container-low transition-all flex items-center gap-3">
						<i class="fa-solid fa-wallet"></i>
						<span><?php esc_html_e( 'PayPal Setup', 'digital-library-membership' ); ?></span>
					</button>
					<?php if ( class_exists( 'WooCommerce' ) ) : ?>
					<button type="button" onclick="switchSettingsTab('woocommerce')" id="tab-settings-woocommerce" class="w-full text-left px-5 py-3 rounded-xl font-bold text-sm text-secondary hover:bg-surface-container-low transition-all flex items-center gap-3">
						<i class="fa-brands fa-woocommerce"></i>
						<span><?php esc_html_e( 'WooCommerce Setup', 'digital-library-membership' ); ?></span>
					</button>
					<?php endif; ?>
					<button type="button" onclick="switchSettingsTab('social')" id="tab-settings-social" class="w-full text-left px-5 py-3 rounded-xl font-bold text-sm text-secondary hover:bg-surface-container-low transition-all flex items-center gap-3">
						<i class="fa-solid fa-share-nodes"></i>
						<span><?php esc_html_e( 'Social Login', 'digital-library-membership' ); ?></span>
					</button>
					<button type="button" onclick="switchSettingsTab('security')" id="tab-settings-security" class="w-full text-left px-5 py-3 rounded-xl font-bold text-sm text-secondary hover:bg-surface-container-low transition-all flex items-center gap-3">
						<i class="fa-solid fa-shield-halved"></i>
						<span><?php esc_html_e( 'Security & Legal', 'digital-library-membership' ); ?></span>
					</button>
					<button type="button" onclick="switchSettingsTab('demo')" id="tab-settings-demo" class="w-full text-left px-5 py-3 rounded-xl font-bold text-sm text-secondary hover:bg-surface-container-low transition-all flex items-center gap-3">
						<i class="fa-solid fa-database"></i>
						<span><?php esc_html_e( 'Demo Data', 'digital-library-membership' ); ?></span>
					</button>
				</div>

				<!-- Settings Forms container -->
				<div class="lg:col-span-8 bg-white border border-outline-variant/20 rounded-2xl p-8 shadow-sm mb-8">
					<form method="post" action="options.php">
						<?php settings_fields( 'dlm_settings_group' ); ?>
						
						<!-- Pricing & Manual Instructions Settings Panel -->
						<div id="panel-settings-general" class="space-y-6">
							<!-- Payment Method Switcher Card -->
							<div class="bg-gradient-to-br from-amber-500/10 via-primary/5 to-transparent p-6 rounded-2xl border border-primary/20 space-y-4">
								<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
									<div class="space-y-1.5">
										<div class="flex flex-wrap items-center gap-2">
											<h4 class="text-sm font-bold text-on-surface uppercase tracking-wider">Payment Method Engine</h4>
											<span class="inline-flex items-center gap-1.5 px-3 py-1 bg-amber-100 text-amber-800 text-[11px] font-bold rounded-full border border-amber-200">
												<i class="fa-solid fa-shield-halved text-[10px]"></i>
												We recommend enabling WooCommerce payment for improved security
											</span>
										</div>
										<p class="text-xs text-secondary leading-relaxed">Choose how digital book purchases and subscription memberships are processed across the library.</p>
									</div>
									<div class="shrink-0">
										<select name="dlm_payment_engine" id="dlm_payment_engine_select" class="px-4 py-2.5 rounded-xl border border-outline-variant/40 focus:border-primary focus:ring-0 text-xs font-bold bg-white shadow-sm text-on-surface">
											<option value="default" <?php selected( get_option( 'dlm_payment_engine', 'default' ), 'default' ); ?>>Default Engine (Direct Stripe / PayPal)</option>
											<option value="woocommerce" <?php selected( get_option( 'dlm_payment_engine', 'default' ), 'woocommerce' ); ?>>WooCommerce Headless (Recommended)</option>
										</select>
									</div>
								</div>
								<div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-3 text-[11px] text-secondary border-t border-outline-variant/10">
									<div class="flex items-start gap-2">
										<i class="fa-solid fa-circle-check text-green-600 mt-0.5 shrink-0"></i>
										<span><strong>Default Engine:</strong> Direct popups & redirects via plugin Stripe & PayPal credentials.</span>
									</div>
									<div class="flex items-start gap-2">
										<i class="fa-solid fa-circle-check text-primary mt-0.5 shrink-0"></i>
										<span><strong>WooCommerce Engine:</strong> Headless orders, 100+ gateways, automated refund revocation & time-limited downloads.</span>
									</div>
								</div>
							</div>

							<!-- Payment Gateways Visibility Switchers -->
							<div class="bg-surface-container-lowest border border-outline-variant/30 rounded-2xl p-6 space-y-4 shadow-sm">
								<div class="flex items-center justify-between border-b border-outline-variant/10 pb-3">
									<div>
										<h4 class="text-sm font-bold text-on-surface uppercase tracking-wider"><?php esc_html_e( 'Frontend Payment Gateways Visibility', 'digital-library-membership' ); ?></h4>
										<p class="text-xs text-secondary mt-0.5"><?php esc_html_e( 'Enable or disable payment methods displayed to members on the Member Dashboard checkout screen.', 'digital-library-membership' ); ?></p>
									</div>
								</div>

								<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
									<!-- WooCommerce Switcher -->
									<div class="flex items-center justify-between p-4 bg-surface-container-low rounded-xl border border-outline-variant/20 hover:border-primary/30 transition-all">
										<div class="flex items-center gap-3">
											<div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center text-lg shrink-0">
												<i class="fa-brands fa-woocommerce"></i>
											</div>
											<div>
												<p class="text-sm font-bold text-on-surface"><?php esc_html_e( 'WooCommerce Gateway', 'digital-library-membership' ); ?></p>
												<p class="text-[11px] text-secondary"><?php esc_html_e( 'Store payment gateways & headless order pay', 'digital-library-membership' ); ?></p>
											</div>
										</div>
										<div>
											<select name="dlm_enable_woocommerce_gateway" class="px-3 py-1.5 rounded-lg border border-outline-variant/40 text-xs font-bold bg-white text-on-surface">
												<option value="yes" <?php selected( get_option( 'dlm_enable_woocommerce_gateway', 'yes' ), 'yes' ); ?>><?php esc_html_e( 'Enabled', 'digital-library-membership' ); ?></option>
												<option value="no" <?php selected( get_option( 'dlm_enable_woocommerce_gateway', 'yes' ), 'no' ); ?>><?php esc_html_e( 'Disabled', 'digital-library-membership' ); ?></option>
											</select>
										</div>
									</div>

									<!-- Stripe Switcher -->
									<div class="flex items-center justify-between p-4 bg-surface-container-low rounded-xl border border-outline-variant/20 hover:border-primary/30 transition-all">
										<div class="flex items-center gap-3">
											<div class="w-10 h-10 rounded-xl bg-blue-500/10 text-blue-600 flex items-center justify-center text-lg shrink-0">
												<i class="fa-solid fa-credit-card"></i>
											</div>
											<div>
												<p class="text-sm font-bold text-on-surface"><?php esc_html_e( 'Stripe Direct Gateway', 'digital-library-membership' ); ?></p>
												<p class="text-[11px] text-secondary"><?php esc_html_e( 'Direct credit/debit card popup checkout', 'digital-library-membership' ); ?></p>
											</div>
										</div>
										<div>
											<select name="dlm_enable_stripe_gateway" class="px-3 py-1.5 rounded-lg border border-outline-variant/40 text-xs font-bold bg-white text-on-surface">
												<option value="yes" <?php selected( get_option( 'dlm_enable_stripe_gateway', 'yes' ), 'yes' ); ?>><?php esc_html_e( 'Enabled', 'digital-library-membership' ); ?></option>
												<option value="no" <?php selected( get_option( 'dlm_enable_stripe_gateway', 'yes' ), 'no' ); ?>><?php esc_html_e( 'Disabled', 'digital-library-membership' ); ?></option>
											</select>
										</div>
									</div>

									<!-- PayPal Switcher -->
									<div class="flex items-center justify-between p-4 bg-surface-container-low rounded-xl border border-outline-variant/20 hover:border-primary/30 transition-all">
										<div class="flex items-center gap-3">
											<div class="w-10 h-10 rounded-xl bg-indigo-500/10 text-indigo-600 flex items-center justify-center text-lg shrink-0">
												<i class="fa-brands fa-paypal"></i>
											</div>
											<div>
												<p class="text-sm font-bold text-on-surface"><?php esc_html_e( 'PayPal Direct Gateway', 'digital-library-membership' ); ?></p>
												<p class="text-[11px] text-secondary"><?php esc_html_e( 'Direct PayPal smart buttons checkout', 'digital-library-membership' ); ?></p>
											</div>
										</div>
										<div>
											<select name="dlm_enable_paypal_gateway" class="px-3 py-1.5 rounded-lg border border-outline-variant/40 text-xs font-bold bg-white text-on-surface">
												<option value="yes" <?php selected( get_option( 'dlm_enable_paypal_gateway', 'yes' ), 'yes' ); ?>><?php esc_html_e( 'Enabled', 'digital-library-membership' ); ?></option>
												<option value="no" <?php selected( get_option( 'dlm_enable_paypal_gateway', 'yes' ), 'no' ); ?>><?php esc_html_e( 'Disabled', 'digital-library-membership' ); ?></option>
											</select>
										</div>
									</div>

									<!-- Manual Bank Transfer Switcher -->
									<div class="flex items-center justify-between p-4 bg-surface-container-low rounded-xl border border-outline-variant/20 hover:border-primary/30 transition-all">
										<div class="flex items-center gap-3">
											<div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-700 flex items-center justify-center text-lg shrink-0">
												<i class="fa-solid fa-building-columns"></i>
											</div>
											<div>
												<p class="text-sm font-bold text-on-surface"><?php esc_html_e( 'Bank Transfer (Manual)', 'digital-library-membership' ); ?></p>
												<p class="text-[11px] text-secondary"><?php esc_html_e( 'Manual instructions with admin approval', 'digital-library-membership' ); ?></p>
											</div>
										</div>
										<div>
											<select name="dlm_enable_manual_gateway" class="px-3 py-1.5 rounded-lg border border-outline-variant/40 text-xs font-bold bg-white text-on-surface">
												<option value="yes" <?php selected( get_option( 'dlm_enable_manual_gateway', 'yes' ), 'yes' ); ?>><?php esc_html_e( 'Enabled', 'digital-library-membership' ); ?></option>
												<option value="no" <?php selected( get_option( 'dlm_enable_manual_gateway', 'yes' ), 'no' ); ?>><?php esc_html_e( 'Disabled', 'digital-library-membership' ); ?></option>
											</select>
										</div>
									</div>
								</div>
							</div>

							<h3 class="text-lg font-bold text-on-surface border-b border-outline-variant/10 pb-3">Parameters & Payment Instructions</h3>
							
							<!-- Plans & Packages Callout Banner -->
							<div class="bg-primary/5 border border-primary/20 rounded-2xl p-5 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
								<div>
									<h4 class="text-sm font-bold text-primary flex items-center gap-2">
										<i class="fa-solid fa-layer-group"></i>
										Subscription Packages & Pricing Management
									</h4>
									<p class="text-xs text-secondary mt-1">
										Membership plans, prices, intervals, and bullet features are now managed centrally in the dedicated <strong>Plans & Packages</strong> manager.
									</p>
								</div>
								<button type="button" onclick="navigateSpa('plans')" class="px-4 py-2 bg-primary text-white font-semibold text-xs rounded-xl hover:shadow-md transition-all shrink-0">
									Manage Packages &rarr;
								</button>
							</div>

							<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
								<div class="space-y-1">
									<label class="text-xs font-bold text-on-surface-variant uppercase">Plugin Currency Code</label>
									<input class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm" type="text" name="dlm_currency" value="<?php echo esc_attr( get_option( 'dlm_currency', 'USD' ) ); ?>" placeholder="e.g. USD">
								</div>
								<div class="space-y-1">
									<label class="text-xs font-bold text-on-surface-variant uppercase">Max Book Upload Size (MB)</label>
									<input class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm" type="number" name="dlm_max_upload_size" value="<?php echo esc_attr( get_option( 'dlm_max_upload_size', '50' ) ); ?>" required>
									<span class="text-[11px] text-secondary block mt-1">Server limit: <?php echo esc_html( $this->get_server_max_upload_size() ); ?> MB</span>
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
							<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center border-b border-outline-variant/10 pb-3 gap-2">
								<h3 class="text-lg font-bold text-on-surface">Stripe Setup</h3>
								<?php 
								$stripe_conn = dlm_get_stripe_connection_status();
								if ( $stripe_conn['status'] === 'connected' ) : ?>
									<span class="inline-flex items-center gap-1.5 px-3 py-1 bg-green-50 border border-green-200 text-green-700 rounded-full text-[11px] font-bold">
										<span class="w-2.5 h-2.5 rounded-full bg-green-500 animate-pulse"></span>
										<?php esc_html_e( 'CONNECTED', 'digital-library-membership' ); ?>
										<?php if ( ! empty( $stripe_conn['email'] ) ) echo ' (' . esc_html( $stripe_conn['email'] ) . ')'; ?>
									</span>
								<?php elseif ( $stripe_conn['status'] === 'failed' ) : ?>
									<span class="inline-flex items-center gap-1.5 px-3 py-1 bg-red-50 border border-red-200 text-red-700 rounded-full text-[11px] font-bold" title="<?php echo esc_attr( $stripe_conn['message'] ); ?>">
										<span class="w-2.5 h-2.5 rounded-full bg-red-500"></span>
										<?php esc_html_e( 'NOT CONNECTED', 'digital-library-membership' ); ?>
									</span>
								<?php else : ?>
									<span class="inline-flex items-center gap-1.5 px-3 py-1 bg-surface-container border border-outline-variant/20 text-secondary rounded-full text-[11px] font-bold">
										<span class="w-2.5 h-2.5 rounded-full bg-secondary"></span>
										<?php esc_html_e( 'NOT SET', 'digital-library-membership' ); ?>
									</span>
								<?php endif; ?>
							</div>
							
							<?php if ( $stripe_conn['status'] === 'failed' ) : ?>
								<div class="p-3.5 bg-red-50/50 border border-red-100 rounded-xl text-xs text-red-800 leading-relaxed">
									<strong><?php esc_html_e( 'Connection Failed Cause:', 'digital-library-membership' ); ?></strong> <?php echo esc_html( $stripe_conn['message'] ); ?>
								</div>
							<?php endif; ?>

							<div class="flex items-center justify-between p-4 bg-surface-container-low rounded-xl border border-outline-variant/20">
								<div>
									<p class="text-sm font-bold text-on-surface"><?php esc_html_e( 'Enable Stripe on Frontend', 'digital-library-membership' ); ?></p>
									<p class="text-xs text-secondary"><?php esc_html_e( 'Allow members to choose Stripe Card checkout on member dashboard.', 'digital-library-membership' ); ?></p>
								</div>
								<select name="dlm_enable_stripe_gateway" class="px-3 py-1.5 rounded-lg border border-outline-variant/40 text-xs font-bold bg-white text-on-surface">
									<option value="yes" <?php selected( get_option( 'dlm_enable_stripe_gateway', 'yes' ), 'yes' ); ?>><?php esc_html_e( 'Enabled', 'digital-library-membership' ); ?></option>
									<option value="no" <?php selected( get_option( 'dlm_enable_stripe_gateway', 'yes' ), 'no' ); ?>><?php esc_html_e( 'Disabled', 'digital-library-membership' ); ?></option>
								</select>
							</div>

							<div class="space-y-4">
								<div class="space-y-1">
									<label class="text-xs font-bold text-on-surface-variant uppercase">Stripe Publishable Key</label>
									<input class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm" type="text" name="dlm_stripe_publishable_key" value="<?php echo esc_attr( get_option( 'dlm_stripe_publishable_key' ) ); ?>" placeholder="pk_test_...">
								</div>
								<div class="space-y-1">
									<label class="text-xs font-bold text-on-surface-variant uppercase">Stripe Secret Key</label>
									<input class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm" type="password" name="dlm_stripe_secret_key" value="<?php echo esc_attr( get_option( 'dlm_stripe_secret_key' ) ); ?>" placeholder="sk_test_...">
								</div>
								<div class="space-y-1">
									<label class="text-xs font-bold text-on-surface-variant uppercase">Stripe Webhook Signing Secret</label>
									<input class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm" type="password" name="dlm_stripe_webhook_secret" value="<?php echo esc_attr( get_option( 'dlm_stripe_webhook_secret' ) ); ?>" placeholder="whsec_...">
								</div>
							</div>
						</div>

						<!-- PayPal Configuration Panel -->
						<div id="panel-settings-paypal" class="space-y-6 hidden">
							<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center border-b border-outline-variant/10 pb-3 gap-2">
								<h3 class="text-lg font-bold text-on-surface">PayPal Setup</h3>
								<?php 
								$paypal_conn = dlm_get_paypal_connection_status();
								if ( $paypal_conn['status'] === 'connected' ) : ?>
									<span class="inline-flex items-center gap-1.5 px-3 py-1 bg-green-50 border border-green-200 text-green-700 rounded-full text-[11px] font-bold">
										<span class="w-2.5 h-2.5 rounded-full bg-green-500 animate-pulse"></span>
										<?php esc_html_e( 'CONNECTED', 'digital-library-membership' ); ?>
									</span>
								<?php elseif ( $paypal_conn['status'] === 'failed' ) : ?>
									<span class="inline-flex items-center gap-1.5 px-3 py-1 bg-red-50 border border-red-200 text-red-700 rounded-full text-[11px] font-bold" title="<?php echo esc_attr( $paypal_conn['message'] ); ?>">
										<span class="w-2.5 h-2.5 rounded-full bg-red-500"></span>
										<?php esc_html_e( 'NOT CONNECTED', 'digital-library-membership' ); ?>
									</span>
								<?php else : ?>
									<span class="inline-flex items-center gap-1.5 px-3 py-1 bg-surface-container border border-outline-variant/20 text-secondary rounded-full text-[11px] font-bold">
										<span class="w-2.5 h-2.5 rounded-full bg-secondary"></span>
										<?php esc_html_e( 'NOT SET', 'digital-library-membership' ); ?>
									</span>
								<?php endif; ?>
							</div>

							<?php if ( $paypal_conn['status'] === 'failed' ) : ?>
								<div class="p-3.5 bg-red-50/50 border border-red-100 rounded-xl text-xs text-red-800 leading-relaxed">
									<strong><?php esc_html_e( 'Connection Failed Cause:', 'digital-library-membership' ); ?></strong> <?php echo esc_html( $paypal_conn['message'] ); ?>
								</div>
							<?php endif; ?>

							<div class="flex items-center justify-between p-4 bg-surface-container-low rounded-xl border border-outline-variant/20">
								<div>
									<p class="text-sm font-bold text-on-surface"><?php esc_html_e( 'Enable PayPal on Frontend', 'digital-library-membership' ); ?></p>
									<p class="text-xs text-secondary"><?php esc_html_e( 'Allow members to choose PayPal Smart Buttons on member dashboard.', 'digital-library-membership' ); ?></p>
								</div>
								<select name="dlm_enable_paypal_gateway" class="px-3 py-1.5 rounded-lg border border-outline-variant/40 text-xs font-bold bg-white text-on-surface">
									<option value="yes" <?php selected( get_option( 'dlm_enable_paypal_gateway', 'yes' ), 'yes' ); ?>><?php esc_html_e( 'Enabled', 'digital-library-membership' ); ?></option>
									<option value="no" <?php selected( get_option( 'dlm_enable_paypal_gateway', 'yes' ), 'no' ); ?>><?php esc_html_e( 'Disabled', 'digital-library-membership' ); ?></option>
								</select>
							</div>

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
									<label class="text-xs font-bold text-on-surface-variant uppercase">PayPal Webhook ID</label>
									<input class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm" type="text" name="dlm_paypal_webhook_id" value="<?php echo esc_attr( get_option( 'dlm_paypal_webhook_id' ) ); ?>">
								</div>
							</div>
						</div>

						<?php if ( class_exists( 'WooCommerce' ) ) : ?>
						<!-- WooCommerce Configuration Panel -->
						<div id="panel-settings-woocommerce" class="space-y-6 hidden">
							<div class="flex items-center justify-between border-b border-outline-variant/10 pb-3">
								<h3 class="text-lg font-bold text-on-surface"><?php esc_html_e( 'WooCommerce Headless Engine', 'digital-library-membership' ); ?></h3>
								<select name="dlm_enable_woocommerce_gateway" class="px-3 py-1.5 rounded-lg border border-outline-variant/40 text-xs font-bold bg-white text-on-surface">
									<option value="yes" <?php selected( get_option( 'dlm_enable_woocommerce_gateway', 'yes' ), 'yes' ); ?>><?php esc_html_e( 'Enabled', 'digital-library-membership' ); ?></option>
									<option value="no" <?php selected( get_option( 'dlm_enable_woocommerce_gateway', 'yes' ), 'no' ); ?>><?php esc_html_e( 'Disabled', 'digital-library-membership' ); ?></option>
								</select>
							</div>
							<div class="p-4 bg-primary/5 border border-primary/20 rounded-2xl text-xs text-on-surface space-y-2">
								<p>
									<strong><i class="fa-solid fa-wand-magic-sparkles text-primary"></i> Automated Virtual Product Sync:</strong>
									When you create or update packages under <strong>Plans & Packages</strong>, hidden WooCommerce virtual products are automatically generated and linked in the background.
								</p>
								<p class="text-secondary">
									Customer checkout skips the standard cart and directs directly to WooCommerce payments. All catalog items remain hidden from public store views.
								</p>
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
								<p class="text-xs text-secondary leading-relaxed mb-3">Protects checkout, registration, and login screens from automated attacks. <a href="https://www.google.com/recaptcha/admin/create" target="_blank" class="text-primary hover:underline font-semibold inline-flex items-center gap-1 ml-1">Create ReCAPTCHA Keys <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i></a></p>

								<!-- Connection status badge -->
								<div class="mb-4">
									<?php 
									$recaptcha_conn = dlm_get_recaptcha_connection_status(); 
									if ( $recaptcha_conn['status'] === 'connected' ) : ?>
										<div class="flex items-center gap-2 p-3.5 bg-green-50 border border-green-200 text-green-700 rounded-xl text-xs font-semibold">
											<span class="w-2.5 h-2.5 rounded-full bg-green-600 animate-pulse shrink-0"></span>
											<span><?php echo esc_html( $recaptcha_conn['message'] ); ?></span>
										</div>
									<?php elseif ( $recaptcha_conn['status'] === 'testing' ) : ?>
										<div class="flex items-center gap-2 p-3.5 bg-blue-50 border border-blue-200 text-blue-700 rounded-xl text-xs font-semibold">
											<span class="w-2.5 h-2.5 rounded-full bg-blue-600 shrink-0"></span>
											<span><?php echo esc_html( $recaptcha_conn['message'] ); ?></span>
										</div>
									<?php elseif ( $recaptcha_conn['status'] === 'failed' ) : ?>
										<div class="flex flex-col gap-1.5 p-3.5 bg-red-50 border border-red-200 text-red-700 rounded-xl text-xs font-semibold">
											<div class="flex items-center gap-2">
												<span class="w-2.5 h-2.5 rounded-full bg-red-600 shrink-0"></span>
												<span><?php esc_html_e( 'Not Connected to ReCAPTCHA', 'digital-library-membership' ); ?></span>
											</div>
											<p class="text-[11px] text-red-600 font-normal leading-relaxed"><strong><?php esc_html_e( 'Connection Failed Cause:', 'digital-library-membership' ); ?></strong> <?php echo esc_html( $recaptcha_conn['message'] ); ?></p>
										</div>
									<?php else : ?>
										<div class="flex items-center gap-2 p-3.5 bg-surface-container-high border border-outline-variant/30 text-secondary rounded-xl text-xs font-semibold">
											<span class="w-2.5 h-2.5 rounded-full bg-outline-variant shrink-0"></span>
											<span><?php echo esc_html( $recaptcha_conn['message'] ); ?></span>
										</div>
									<?php endif; ?>
								</div>

								<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
									<div class="space-y-1">
										<label class="text-xs font-bold text-on-surface-variant uppercase">ReCAPTCHA Mode</label>
										<select name="dlm_recaptcha_mode" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm">
											<option value="production" <?php selected( get_option( 'dlm_recaptcha_mode', 'production' ), 'production' ); ?>><?php esc_html_e( 'Live Production Mode', 'digital-library-membership' ); ?></option>
											<option value="testing" <?php selected( get_option( 'dlm_recaptcha_mode' ), 'testing' ); ?>><?php esc_html_e( 'Developer Testing Mode', 'digital-library-membership' ); ?></option>
										</select>
									</div>
									<div class="space-y-1">
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

								<div class="border-t border-outline-variant/10 pt-4 mt-4"></div>

								<h4 class="text-xs font-bold text-primary uppercase tracking-wider">GitHub Plugin Updates</h4>
								<p class="text-xs text-secondary leading-relaxed mb-3">Configure updates from a private GitHub repository if needed. You can also define the <code>DLM_GITHUB_TOKEN</code> constant in your <code>wp-config.php</code> file to bypass this setting.</p>

								<div class="grid grid-cols-1 gap-4">
									<div class="space-y-1">
										<label class="text-xs font-bold text-on-surface-variant uppercase">GitHub Personal Access Token (PAT)</label>
										<?php if ( defined( 'DLM_GITHUB_TOKEN' ) ) : ?>
											<input class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 bg-surface-container-low text-sm text-secondary cursor-not-allowed" type="text" value="Defined in wp-config.php" disabled>
											<span class="text-[11px] text-primary block mt-1"><i class="fa-solid fa-circle-check"></i> Active via constant configuration.</span>
										<?php else : ?>
											<input class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm" type="password" name="dlm_github_token" value="<?php echo esc_attr( get_option( 'dlm_github_token' ) ); ?>" placeholder="ghp_...">
											<span class="text-[11px] text-secondary block mt-1">Provide a personal access token with 'repo' scope if the repository is private.</span>
										<?php endif; ?>
									</div>
								</div>

								<div class="border-t border-outline-variant/10 pt-4 mt-4"></div>

								<h4 class="text-xs font-bold text-primary uppercase tracking-wider"><?php esc_html_e( 'Uninstallation & Data Retention', 'digital-library-membership' ); ?></h4>
								<p class="text-xs text-secondary leading-relaxed mb-3"><?php esc_html_e( 'Configure cleanup behavior when this plugin is deleted via the WordPress Plugins menu.', 'digital-library-membership' ); ?></p>

								<div class="flex items-center gap-3 p-4 bg-surface-container-lowest border border-outline-variant/20 rounded-xl">
									<input type="checkbox" id="dlm_delete_data_on_uninstall" name="dlm_delete_data_on_uninstall" value="1" <?php checked( get_option( 'dlm_delete_data_on_uninstall', '0' ), '1' ); ?> class="w-4 h-4 rounded border-outline-variant/30 text-primary focus:ring-primary">
									<label for="dlm_delete_data_on_uninstall" class="text-xs font-bold text-on-surface cursor-pointer">
										<?php esc_html_e( 'Delete all plugin database tables, demo records, and settings when uninstalled', 'digital-library-membership' ); ?>
									</label>
								</div>
							</div>
						</div>

						<!-- Social Sign-In Panel (Google & Apple) -->
						<div id="panel-settings-social" class="space-y-6 hidden">
							<div class="border-b border-outline-variant/10 pb-3">
								<h3 class="text-lg font-bold text-on-surface"><?php esc_html_e( 'Social Sign-In Configuration', 'digital-library-membership' ); ?></h3>
								<p class="text-xs text-secondary"><?php esc_html_e( 'Allow members to log in and register instantly with one click using their Google or Apple accounts without filling manual forms.', 'digital-library-membership' ); ?></p>
							</div>

							<!-- Step-by-Step Credentials Guide (Shared Partial) -->
							<div class="space-y-2">
								<h4 class="text-xs font-bold text-primary uppercase tracking-wider flex items-center gap-2">
									<i class="fa-solid fa-book-open-reader"></i>
									<?php esc_html_e( 'Setup & Credential Instructions', 'digital-library-membership' ); ?>
								</h4>
								<?php require DLM_PATH . 'admin/templates/partials/social-login-guide.php'; ?>
							</div>

							<!-- Google Sign-In Card -->
							<div class="border border-outline-variant/20 rounded-2xl p-6 bg-surface-container-lowest space-y-4">
								<div class="flex items-center justify-between">
									<div class="flex items-center gap-3">
										<div class="w-10 h-10 rounded-xl bg-white shadow-sm flex items-center justify-center border border-outline-variant/20">
											<svg class="w-5 h-5" viewBox="0 0 24 24">
												<path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
												<path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
												<path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
												<path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
											</svg>
										</div>
										<div>
											<h4 class="text-sm font-bold text-on-surface"><?php esc_html_e( 'Sign in with Google', 'digital-library-membership' ); ?></h4>
											<p class="text-xs text-secondary"><?php esc_html_e( 'Enable seamless OAuth 2.0 authentication for Google accounts.', 'digital-library-membership' ); ?></p>
										</div>
									</div>
									<label class="relative inline-flex items-center cursor-pointer">
										<input type="checkbox" name="dlm_enable_google_login" value="1" <?php checked( get_option( 'dlm_enable_google_login', '0' ), '1' ); ?> class="sr-only peer">
										<div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
									</label>
								</div>

								<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
									<div class="space-y-1">
										<label class="text-xs font-bold text-on-surface-variant uppercase"><?php esc_html_e( 'Google Client ID', 'digital-library-membership' ); ?></label>
										<input class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm" type="text" name="dlm_google_client_id" value="<?php echo esc_attr( get_option( 'dlm_google_client_id' ) ); ?>" placeholder="<?php esc_attr_e( 'Enter your Google OAuth Client ID', 'digital-library-membership' ); ?>">
									</div>
									<div class="space-y-1">
										<label class="text-xs font-bold text-on-surface-variant uppercase"><?php esc_html_e( 'Google Client Secret', 'digital-library-membership' ); ?></label>
										<input class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm" type="password" name="dlm_google_client_secret" value="<?php echo esc_attr( get_option( 'dlm_google_client_secret' ) ); ?>" placeholder="e.g. GOCSPX-xxxx...">
									</div>
								</div>
							</div>

							<!-- Apple Sign-In Card -->
							<div class="border border-outline-variant/20 rounded-2xl p-6 bg-surface-container-lowest space-y-4">
								<div class="flex items-center justify-between">
									<div class="flex items-center gap-3">
										<div class="w-10 h-10 rounded-xl bg-black text-white shadow-sm flex items-center justify-center">
											<i class="fa-brands fa-apple text-xl"></i>
										</div>
										<div>
											<h4 class="text-sm font-bold text-on-surface"><?php esc_html_e( 'Sign in with Apple', 'digital-library-membership' ); ?></h4>
											<p class="text-xs text-secondary"><?php esc_html_e( 'Enable Sign in with Apple for iOS and web visitors.', 'digital-library-membership' ); ?></p>
										</div>
									</div>
									<label class="relative inline-flex items-center cursor-pointer">
										<input type="checkbox" name="dlm_enable_apple_login" value="1" <?php checked( get_option( 'dlm_enable_apple_login', '0' ), '1' ); ?> class="sr-only peer">
										<div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
									</label>
								</div>

								<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
									<div class="space-y-1">
										<label class="text-xs font-bold text-on-surface-variant uppercase"><?php esc_html_e( 'Services ID', 'digital-library-membership' ); ?></label>
										<input class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm" type="text" name="dlm_apple_services_id" value="<?php echo esc_attr( get_option( 'dlm_apple_services_id' ) ); ?>" placeholder="e.g. com.example.login">
									</div>
									<div class="space-y-1">
										<label class="text-xs font-bold text-on-surface-variant uppercase"><?php esc_html_e( 'Team ID (10 chars)', 'digital-library-membership' ); ?></label>
										<input class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm" type="text" name="dlm_apple_team_id" value="<?php echo esc_attr( get_option( 'dlm_apple_team_id' ) ); ?>" placeholder="e.g. A1B2C3D4E5">
									</div>
									<div class="space-y-1">
										<label class="text-xs font-bold text-on-surface-variant uppercase"><?php esc_html_e( 'Key ID (10 chars)', 'digital-library-membership' ); ?></label>
										<input class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm" type="text" name="dlm_apple_key_id" value="<?php echo esc_attr( get_option( 'dlm_apple_key_id' ) ); ?>" placeholder="e.g. 89ABCDEF01">
									</div>
								</div>

								<div class="space-y-1 pt-2">
									<label class="text-xs font-bold text-on-surface-variant uppercase"><?php esc_html_e( 'Private Key (.p8 contents)', 'digital-library-membership' ); ?></label>
									<textarea name="dlm_apple_private_key" rows="4" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-xs font-mono" placeholder="-----BEGIN PRIVATE KEY-----&#10;MIGTAgEAMBMGByqGSM49AgEGCCqGSM49AwEHBHkwdwIBAQQg...&#10;-----END PRIVATE KEY-----"><?php echo esc_textarea( get_option( 'dlm_apple_private_key' ) ); ?></textarea>
									<span class="text-[11px] text-secondary block mt-0.5"><?php esc_html_e( 'Paste the complete contents of your downloaded AuthKey_*.p8 private key file.', 'digital-library-membership' ); ?></span>
								</div>
							</div>
						</div>

						<!-- Demo Data Management Panel -->
						<div id="panel-settings-demo" class="space-y-6 hidden">
							<div class="flex flex-col sm:flex-row justify-between items-start sm:items-center border-b border-outline-variant/10 pb-3 gap-2">
								<div>
									<h3 class="text-lg font-bold text-on-surface">Demo Data Management</h3>
									<p class="text-xs text-secondary">Populate your digital library with realistic books, membership tiers, users, and orders to test all access models and payment engines.</p>
								</div>
								<?php if ( $is_demo_active ) : ?>
									<span class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-full text-[11px] font-bold">
										<span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
										DEMO DATA ACTIVE
									</span>
								<?php else : ?>
									<span class="inline-flex items-center gap-1.5 px-3 py-1 bg-surface-container border border-outline-variant/20 text-secondary rounded-full text-[11px] font-bold">
										<span class="w-2.5 h-2.5 rounded-full bg-secondary"></span>
										NO DEMO DATA
									</span>
								<?php endif; ?>
							</div>

							<!-- Demo Content Breakdown Cards -->
							<div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
								<div class="bg-surface-container-lowest border border-outline-variant/20 rounded-2xl p-4 text-center">
									<span class="text-2xl font-bold text-primary block"><?php echo intval( $demo_stats['books'] ); ?></span>
									<span class="text-xs font-semibold text-secondary uppercase tracking-wider">Demo Books</span>
								</div>
								<div class="bg-surface-container-lowest border border-outline-variant/20 rounded-2xl p-4 text-center">
									<span class="text-2xl font-bold text-primary block"><?php echo intval( $demo_stats['users'] ); ?></span>
									<span class="text-xs font-semibold text-secondary uppercase tracking-wider">Demo Members</span>
								</div>
								<div class="bg-surface-container-lowest border border-outline-variant/20 rounded-2xl p-4 text-center">
									<span class="text-2xl font-bold text-primary block"><?php echo intval( $demo_stats['purchases'] ); ?></span>
									<span class="text-xs font-semibold text-secondary uppercase tracking-wider">Book Purchases</span>
								</div>
								<div class="bg-surface-container-lowest border border-outline-variant/20 rounded-2xl p-4 text-center">
									<span class="text-2xl font-bold text-primary block"><?php echo intval( $demo_stats['transactions'] ); ?></span>
									<span class="text-xs font-semibold text-secondary uppercase tracking-wider">Transactions</span>
								</div>
							</div>

							<!-- What is included box -->
							<div class="bg-primary/5 border border-primary/20 rounded-2xl p-5 space-y-3">
								<h4 class="text-xs font-bold text-primary uppercase tracking-wider flex items-center gap-2">
									<i class="fa-solid fa-wand-magic-sparkles"></i> What Gets Generated:
								</h4>
								<div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs text-on-surface-variant">
									<div class="flex items-start gap-2">
										<i class="fa-solid fa-circle-check text-primary mt-0.5"></i>
										<span><strong>3 Access Types:</strong> Subscription Only, Purchase Only ($19.99 - $29.99), and Hybrid ($14.99 - $24.99).</span>
									</div>
									<div class="flex items-start gap-2">
										<i class="fa-solid fa-circle-check text-primary mt-0.5"></i>
										<span><strong>Scheduled Publishing:</strong> 1 future-dated book to test release countdown and scheduling filters.</span>
									</div>
									<div class="flex items-start gap-2">
										<i class="fa-solid fa-circle-check text-primary mt-0.5"></i>
										<span><strong>Taxonomies:</strong> 5 Categories & 6 curated Tags (Bestseller, Essential, Research, Tutorial).</span>
									</div>
									<div class="flex items-start gap-2">
										<i class="fa-solid fa-circle-check text-primary mt-0.5"></i>
										<span><strong>Purchases & Transactions:</strong> Realistic data mix covering Completed, Pending, and Refunded orders.</span>
									</div>
								</div>
							</div>

							<!-- Action Buttons -->
							<div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-4 border-t border-outline-variant/10">
								<div>
									<?php if ( $is_demo_active ) : ?>
										<p class="text-xs text-emerald-700 font-medium"><i class="fa-solid fa-circle-check"></i> Demo content is currently loaded. You can explore the Library and Purchases tabs.</p>
									<?php else : ?>
										<p class="text-xs text-secondary">Click the button to generate the complete testing dataset in 1-click.</p>
									<?php endif; ?>
								</div>

								<div class="flex items-center gap-3 shrink-0">
									<?php if ( ! $is_demo_active ) : ?>
										<button type="button" id="dlm-btn-import-demo" class="px-5 py-2.5 rounded-xl font-bold text-sm bg-primary text-white hover:opacity-90 transition-all flex items-center gap-2 shadow-sm">
											<i class="fa-solid fa-cloud-arrow-down"></i>
											Import Demo Data
										</button>
									<?php else : ?>
										<button type="button" id="dlm-btn-remove-demo" class="px-5 py-2.5 rounded-xl font-bold text-sm bg-red-600 text-white hover:bg-red-700 transition-all flex items-center gap-2 shadow-sm">
											<i class="fa-solid fa-trash-can"></i>
											Remove Demo Data
										</button>
									<?php endif; ?>
								</div>
							</div>
						</div>

						<!-- Setup Flow Relaunch Tool -->
						<div class="mt-8 pt-6 border-t border-outline-variant/10">
							<h3 class="text-sm font-bold text-on-surface uppercase tracking-wider mb-2">Setup Flow Wizard</h3>
							<p class="text-xs text-secondary mb-4">Click below to relaunch the setup wizard to easily configure pages, payment options, and recaptcha settings again.</p>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=dlm-setup-wizard' ) ); ?>" class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold bg-surface-container-high hover:bg-surface-container-highest border border-outline-variant/30 text-on-surface transition-all">
								<i class="fa-solid fa-wand-magic-sparkles text-xs"></i>
								Setup Widget
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
		<a class="flex flex-col items-center justify-center text-secondary transition-all cursor-pointer" data-nav="plans" onclick="navigateSpa('plans')">
			<i class="fa-solid fa-layer-group"></i>
			<span class="text-[10px] font-bold mt-0.5">Plans</span>
		</a>
		<a class="flex flex-col items-center justify-center text-secondary transition-all cursor-pointer" data-nav="purchases" onclick="navigateSpa('purchases')">
			<i class="fa-solid fa-bag-shopping"></i>
			<span class="text-[10px] font-bold mt-0.5">Purchases</span>
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
						<label class="text-xs font-bold text-on-surface-variant uppercase">Book Access Model *</label>
						<select name="access_type" id="add-book-access-type" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm">
							<option value="subscription_only">Subscription Only (Active members read online)</option>
							<option value="purchase_only">Purchase Only (Individual purchase required for read + download)</option>
							<option value="hybrid">Both / Hybrid (Free for subscribers, non-subscribers can buy)</option>
						</select>
					</div>
					<div class="space-y-1" id="add-book-price-container" style="display: none;">
						<label class="text-xs font-bold text-on-surface-variant uppercase">Book Purchase Price (<?php echo esc_html( $currency ); ?>) *</label>
						<div class="relative">
							<span class="absolute left-4 top-2.5 text-xs font-bold text-secondary"><?php echo esc_html( $currency ); ?></span>
							<input name="price" id="add-book-price" class="w-full pl-14 pr-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm" placeholder="0.00" type="number" step="0.01" min="0" value="0.00">
						</div>
						<p class="text-[10px] text-secondary">Price in configured plugin currency (<?php echo esc_html( $currency ); ?>).</p>
					</div>
					<div class="space-y-1">
						<label class="text-xs font-bold text-on-surface-variant uppercase">Publish Date & Time (Scheduling)</label>
						<input type="datetime-local" name="publish_date" id="add-book-publish-date" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm">
						<p class="text-[10px] text-secondary">Leave blank for immediate publishing, or set a future date/time to schedule.</p>
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
					<!-- Featured Book Section -->
					<div class="pt-4 border-t border-outline-variant/10 space-y-3 bg-amber-50/50 p-4 rounded-2xl border border-amber-200/50">
						<div class="flex items-center justify-between">
							<div class="flex items-center gap-2.5">
								<div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center text-amber-800 shrink-0">
									<i class="fa-solid fa-star text-sm"></i>
								</div>
								<div>
									<label for="add-book-is-featured" class="text-xs font-bold text-on-surface cursor-pointer">Mark as Featured Book</label>
									<p class="text-[10px] text-secondary">Promote in Member Dashboard Hero Slider & Elementor widgets.</p>
								</div>
							</div>
							<label class="relative inline-flex items-center cursor-pointer">
								<input type="checkbox" name="is_featured" id="add-book-is-featured" value="1" class="sr-only peer dlm-featured-toggle" data-target="#add-featured-fields">
								<div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-600"></div>
							</label>
						</div>

						<div id="add-featured-fields" class="space-y-3 pt-3 border-t border-amber-200/40 hidden">
							<div class="space-y-1">
								<label class="text-xs font-bold text-on-surface-variant uppercase">Featured Title (Optional Override)</label>
								<input name="featured_title" id="add-book-featured-title" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm bg-white" placeholder="Leave empty to use original book title" type="text">
							</div>
							<div class="space-y-1">
								<div class="flex justify-between items-center">
									<label class="text-xs font-bold text-on-surface-variant uppercase">Featured Description / Blurb</label>
									<span class="text-[10px] text-secondary char-count"><span class="char-count-num">0</span>/220</span>
								</div>
								<textarea name="featured_description" id="add-book-featured-desc" maxlength="220" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm resize-none bg-white dlm-char-counter" rows="2" placeholder="Short blurb for hero banner (max 220 characters)..."></textarea>
							</div>
							<div class="space-y-1">
								<label class="text-xs font-bold text-on-surface-variant uppercase">Featured Banner Image (1600x600 px)</label>
								<div class="flex items-center gap-3">
									<div class="w-24 h-12 bg-slate-100 rounded-lg border border-outline-variant/20 flex items-center justify-center text-secondary/30 overflow-hidden shrink-0 relative">
										<img id="add-banner-preview" class="w-full h-full object-cover hidden" alt="Banner Preview">
										<i id="add-banner-placeholder" class="fa-regular fa-image text-xl"></i>
									</div>
									<div class="flex-grow flex gap-2">
										<input type="text" name="featured_banner_url" id="add-book-banner-input" class="w-full px-4 py-2 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-xs bg-white" placeholder="Banner Image URL">
										<input type="hidden" name="featured_banner_id" id="add-book-banner-id" value="0">
										<button type="button" id="add-book-select-banner-btn" class="bg-surface-container-high px-3 py-2 rounded-xl text-xs font-bold hover:bg-surface-container-highest border border-outline-variant/30 shrink-0">Upload</button>
										<button type="button" id="add-book-clear-banner-btn" class="text-error hover:bg-error-container/20 px-2 py-2 rounded-xl text-xs font-bold border border-outline-variant/30 hidden shrink-0" title="Remove Banner"><i class="fa-solid fa-xmark"></i></button>
									</div>
								</div>
								<p class="text-[10px] text-secondary">Falls back to book cover image if left empty.</p>
							</div>
							<div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
								<div class="space-y-1">
									<label class="text-xs font-bold text-on-surface-variant uppercase">CTA Button 1 Label</label>
									<input name="featured_button_1_label" id="add-book-btn1" class="w-full px-4 py-2 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-xs bg-white" placeholder="e.g. Read Now / Pre-Order" type="text">
								</div>
								<div class="space-y-1">
									<label class="text-xs font-bold text-on-surface-variant uppercase">CTA Button 2 Label</label>
									<input name="featured_button_2_label" id="add-book-btn2" class="w-full px-4 py-2 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-xs bg-white" placeholder="e.g. Add to Wishlist" type="text">
								</div>
							</div>
							<div class="space-y-1">
								<label class="text-xs font-bold text-on-surface-variant uppercase">Slide Order / Priority</label>
								<input name="featured_order" id="add-book-featured-order" class="w-full px-4 py-2 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-xs bg-white" placeholder="0" type="number" min="0" value="0">
								<p class="text-[10px] text-secondary">Lower numbers appear first in the slider (0 = Highest priority).</p>
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
						<label class="text-xs font-bold text-on-surface-variant uppercase">Book Access Model *</label>
						<select name="access_type" id="edit-book-access-type" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm">
							<option value="subscription_only">Subscription Only (Active members read online)</option>
							<option value="purchase_only">Purchase Only (Individual purchase required for read + download)</option>
							<option value="hybrid">Both / Hybrid (Free for subscribers, non-subscribers can buy)</option>
						</select>
					</div>
					<div class="space-y-1" id="edit-book-price-container" style="display: none;">
						<label class="text-xs font-bold text-on-surface-variant uppercase">Book Purchase Price (<?php echo esc_html( $currency ); ?>) *</label>
						<div class="relative">
							<span class="absolute left-4 top-2.5 text-xs font-bold text-secondary"><?php echo esc_html( $currency ); ?></span>
							<input name="price" id="edit-book-price" class="w-full pl-14 pr-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm" placeholder="0.00" type="number" step="0.01" min="0" value="0.00">
						</div>
						<p class="text-[10px] text-secondary">Price in configured plugin currency (<?php echo esc_html( $currency ); ?>).</p>
					</div>
					<div class="space-y-1">
						<label class="text-xs font-bold text-on-surface-variant uppercase">Publish Date & Time (Scheduling)</label>
						<input type="datetime-local" name="publish_date" id="edit-book-publish-date" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm">
						<p class="text-[10px] text-secondary">Future scheduled date or leave empty for immediately published.</p>
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
					<!-- Featured Book Section -->
					<div class="pt-4 border-t border-outline-variant/10 space-y-3 bg-amber-50/50 p-4 rounded-2xl border border-amber-200/50">
						<div class="flex items-center justify-between">
							<div class="flex items-center gap-2.5">
								<div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center text-amber-800 shrink-0">
									<i class="fa-solid fa-star text-sm"></i>
								</div>
								<div>
									<label for="edit-book-is-featured" class="text-xs font-bold text-on-surface cursor-pointer">Mark as Featured Book</label>
									<p class="text-[10px] text-secondary">Promote in Member Dashboard Hero Slider & Elementor widgets.</p>
								</div>
							</div>
							<label class="relative inline-flex items-center cursor-pointer">
								<input type="checkbox" name="is_featured" id="edit-book-is-featured" value="1" class="sr-only peer dlm-featured-toggle" data-target="#edit-featured-fields">
								<div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-600"></div>
							</label>
						</div>

						<div id="edit-featured-fields" class="space-y-3 pt-3 border-t border-amber-200/40 hidden">
							<div class="space-y-1">
								<label class="text-xs font-bold text-on-surface-variant uppercase">Featured Title (Optional Override)</label>
								<input name="featured_title" id="edit-book-featured-title" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm bg-white" placeholder="Leave empty to use original book title" type="text">
							</div>
							<div class="space-y-1">
								<div class="flex justify-between items-center">
									<label class="text-xs font-bold text-on-surface-variant uppercase">Featured Description / Blurb</label>
									<span class="text-[10px] text-secondary char-count"><span class="char-count-num">0</span>/220</span>
								</div>
								<textarea name="featured_description" id="edit-book-featured-desc" maxlength="220" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm resize-none bg-white dlm-char-counter" rows="2" placeholder="Short blurb for hero banner (max 220 characters)..."></textarea>
							</div>
							<div class="space-y-1">
								<label class="text-xs font-bold text-on-surface-variant uppercase">Featured Banner Image (1600x600 px)</label>
								<div class="flex items-center gap-3">
									<div class="w-24 h-12 bg-slate-100 rounded-lg border border-outline-variant/20 flex items-center justify-center text-secondary/30 overflow-hidden shrink-0 relative">
										<img id="edit-banner-preview" class="w-full h-full object-cover hidden" alt="Banner Preview">
										<i id="edit-banner-placeholder" class="fa-regular fa-image text-xl"></i>
									</div>
									<div class="flex-grow flex gap-2">
										<input type="text" name="featured_banner_url" id="edit-book-banner-input" class="w-full px-4 py-2 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-xs bg-white" placeholder="Banner Image URL">
										<input type="hidden" name="featured_banner_id" id="edit-book-banner-id" value="0">
										<button type="button" id="edit-book-select-banner-btn" class="bg-surface-container-high px-3 py-2 rounded-xl text-xs font-bold hover:bg-surface-container-highest border border-outline-variant/30 shrink-0">Upload</button>
										<button type="button" id="edit-book-clear-banner-btn" class="text-error hover:bg-error-container/20 px-2 py-2 rounded-xl text-xs font-bold border border-outline-variant/30 hidden shrink-0" title="Remove Banner"><i class="fa-solid fa-xmark"></i></button>
									</div>
								</div>
								<p class="text-[10px] text-secondary">Falls back to book cover image if left empty.</p>
							</div>
							<div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
								<div class="space-y-1">
									<label class="text-xs font-bold text-on-surface-variant uppercase">CTA Button 1 Label</label>
									<input name="featured_button_1_label" id="edit-book-btn1" class="w-full px-4 py-2 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-xs bg-white" placeholder="e.g. Read Now / Pre-Order" type="text">
								</div>
								<div class="space-y-1">
									<label class="text-xs font-bold text-on-surface-variant uppercase">CTA Button 2 Label</label>
									<input name="featured_button_2_label" id="edit-book-btn2" class="w-full px-4 py-2 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-xs bg-white" placeholder="e.g. Add to Wishlist" type="text">
								</div>
							</div>
							<div class="space-y-1">
								<label class="text-xs font-bold text-on-surface-variant uppercase">Slide Order / Priority</label>
								<input name="featured_order" id="edit-book-featured-order" class="w-full px-4 py-2 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-xs bg-white" placeholder="0" type="number" min="0" value="0">
								<p class="text-[10px] text-secondary">Lower numbers appear first in the slider (0 = Highest priority).</p>
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
	<!-- Add Package Modal -->
	<div id="add-package-modal" class="fixed inset-0 z-[1000] items-center justify-center p-4 hidden">
		<div class="absolute inset-0 modal-backdrop" data-close-modal="add-package-modal"></div>
		<div class="relative bg-white w-full max-w-xl rounded-3xl shadow-xl overflow-hidden border border-outline-variant/20 animate-in fade-in zoom-in duration-200 z-10 max-h-[90vh] flex flex-col">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="flex flex-col h-full overflow-hidden">
				<input type="hidden" name="action" value="dlm_save_package">
				<?php wp_nonce_field( 'dlm_package_action_nonce', 'dlm_nonce' ); ?>

				<div class="px-8 py-6 border-b border-outline-variant/10 flex justify-between items-center bg-surface-container-low/30 shrink-0">
					<h3 class="text-lg font-bold text-on-surface">Add New Subscription Package</h3>
					<button type="button" data-close-modal="add-package-modal" class="p-1.5 hover:bg-surface-container-high/50 rounded-full transition-colors"><i class="fa-solid fa-xmark"></i></button>
				</div>
				
				<div class="p-8 space-y-4 overflow-y-auto flex-1 dlm-hover-scrollbar">
					<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
						<div class="space-y-1">
							<label class="text-xs font-bold text-on-surface-variant uppercase">Package Name *</label>
							<input name="package_name" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 text-sm focus:border-primary focus:ring-0" type="text" placeholder="e.g. Premium Monthly" required>
						</div>
						<div class="space-y-1">
							<label class="text-xs font-bold text-on-surface-variant uppercase">Badge Label (Optional)</label>
							<input name="package_badge" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 text-sm focus:border-primary focus:ring-0" type="text" placeholder="e.g. The Scholar, BEST VALUE">
						</div>
					</div>

					<div class="space-y-1">
						<label class="text-xs font-bold text-on-surface-variant uppercase">Short Description</label>
						<input name="package_description" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 text-sm focus:border-primary focus:ring-0" type="text" placeholder="e.g. Unlimited monthly reading access to our entire catalog.">
					</div>

					<div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
						<div class="space-y-1">
							<label class="text-xs font-bold text-on-surface-variant uppercase">Billing Cycle *</label>
							<select name="billing_cycle" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm">
								<option value="monthly">Monthly</option>
								<option value="yearly">Annual / Yearly</option>
								<option value="lifetime">Lifetime Access</option>
							</select>
						</div>
						<div class="space-y-1">
							<label class="text-xs font-bold text-on-surface-variant uppercase">Price ($) *</label>
							<input name="package_price" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 text-sm focus:border-primary focus:ring-0" type="number" step="0.01" min="0" placeholder="e.g. 9.99" required>
						</div>
						<div class="space-y-1">
							<label class="text-xs font-bold text-on-surface-variant uppercase">Status *</label>
							<select name="package_status" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm">
								<option value="active">Active (Public)</option>
								<option value="inactive">Inactive (Retired)</option>
							</select>
						</div>
					</div>

					<div class="space-y-1">
						<label class="text-xs font-bold text-on-surface-variant uppercase">Plan Benefits / Bullet Features (One per line)</label>
						<textarea name="package_features" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-xs font-mono" rows="4" placeholder="Unlimited digital reading&#10;Real-time reading journal logs&#10;Saves streaks & achievements"></textarea>
					</div>

					<!-- Gateway Integrations & Mappings -->
					<div class="border-t border-outline-variant/10 pt-4 space-y-4">
						<div>
							<h4 class="text-xs font-bold text-primary uppercase tracking-wider flex items-center gap-1.5">
								<i class="fa-solid fa-wand-magic-sparkles text-primary"></i>
								Automated Gateway Provisioning & Mappings
							</h4>
							<p class="text-[11px] text-secondary mt-0.5">
								Gateway products and plans are created <strong>automatically</strong> in your payment accounts on save. Leave fields empty to auto-provision, or paste existing IDs to map manually.
							</p>
						</div>

						<div class="space-y-3 bg-surface-container-lowest/50 p-4 rounded-2xl border border-outline-variant/20">
							<!-- Stripe Price ID -->
							<div class="space-y-1">
								<div class="flex justify-between items-baseline">
									<label class="text-[11px] font-bold text-on-surface-variant uppercase flex items-center gap-1">
										<i class="fa-brands fa-stripe text-primary text-base"></i> Stripe Price ID
									</label>
									<span class="text-[10px] text-primary font-bold bg-primary/10 px-2 py-0.5 rounded-md">Auto-Sync on Save</span>
								</div>
								<input name="stripe_price_id" class="w-full px-3.5 py-2 rounded-xl border border-outline-variant/30 text-xs focus:border-primary focus:ring-0 font-mono" type="text" placeholder="Auto-generated (or paste price_...)">
								<p class="text-[10px] text-secondary leading-relaxed">
									Leave blank to automatically create a Product & Price on Stripe via API, or enter an existing <code>price_...</code> ID.
								</p>
							</div>

							<!-- PayPal Plan ID -->
							<div class="space-y-1 pt-2 border-t border-outline-variant/10">
								<div class="flex justify-between items-baseline">
									<label class="text-[11px] font-bold text-on-surface-variant uppercase flex items-center gap-1">
										<i class="fa-brands fa-paypal text-blue-600 text-sm"></i> PayPal Plan ID
									</label>
									<span class="text-[10px] text-blue-700 font-bold bg-blue-50 px-2 py-0.5 rounded-md">Auto-Sync on Save</span>
								</div>
								<input name="paypal_plan_id" class="w-full px-3.5 py-2 rounded-xl border border-outline-variant/30 text-xs focus:border-primary focus:ring-0 font-mono" type="text" placeholder="Auto-generated (or paste P-...)">
								<p class="text-[10px] text-secondary leading-relaxed">
									Leave blank to automatically create an active Catalog Product & Subscription Plan on PayPal, or enter an existing <code>P-...</code> ID.
								</p>
							</div>

							<!-- WooCommerce Virtual Product -->
							<?php if ( class_exists( 'WooCommerce' ) ) : ?>
								<div class="space-y-1 pt-2 border-t border-outline-variant/10">
									<div class="flex justify-between items-baseline">
										<label class="text-[11px] font-bold text-on-surface-variant uppercase flex items-center gap-1">
											<i class="fa-solid fa-bag-shopping text-purple-600 text-xs"></i> WooCommerce Product
										</label>
										<span class="text-[10px] text-green-700 font-bold bg-green-50 px-2 py-0.5 rounded-md">Auto-Generated</span>
									</div>
									<select name="wc_product_id" class="w-full px-3.5 py-2 rounded-xl border border-outline-variant/30 text-xs focus:border-primary focus:ring-0">
										<option value="0"><?php esc_html_e( '— Auto-Generate Virtual Product (Recommended) —', 'digital-library-membership' ); ?></option>
										<?php 
										$wc_prods = get_posts( array( 'post_type' => 'product', 'posts_per_page' => -1 ) );
										foreach ( $wc_prods as $wcp ) {
											echo '<option value="' . intval( $wcp->ID ) . '">' . esc_html( $wcp->post_title ) . ' (#' . intval( $wcp->ID ) . ')</option>';
										}
										?>
									</select>
									<p class="text-[10px] text-secondary leading-relaxed">
										Leave as <em>Auto-Generate</em> to automatically create and sync a hidden virtual WooCommerce product upon save.
									</p>
								</div>
							<?php endif; ?>
						</div>
					</div>
				</div>

				<div class="px-8 py-5 border-t border-outline-variant/10 bg-surface-container-low/30 flex justify-end gap-3 shrink-0">
					<button type="button" data-close-modal="add-package-modal" class="px-5 py-2.5 rounded-xl font-bold text-sm text-secondary hover:bg-secondary-container/30 transition-all">Cancel</button>
					<button type="submit" class="px-5 py-2.5 rounded-xl font-bold text-sm bg-primary text-white hover:opacity-90">Create Package</button>
				</div>
			</form>
		</div>
	</div>

	<!-- Edit Package Modal -->
	<div id="edit-package-modal" class="fixed inset-0 z-[1000] items-center justify-center p-4 hidden">
		<div class="absolute inset-0 modal-backdrop" data-close-modal="edit-package-modal"></div>
		<div class="relative bg-white w-full max-w-xl rounded-3xl shadow-xl overflow-hidden border border-outline-variant/20 animate-in fade-in zoom-in duration-200 z-10 max-h-[90vh] flex flex-col">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="flex flex-col h-full overflow-hidden">
				<input type="hidden" name="action" value="dlm_edit_package">
				<input type="hidden" name="package_id" id="edit-package-id" value="">
				<?php wp_nonce_field( 'dlm_package_action_nonce', 'dlm_nonce' ); ?>

				<div class="px-8 py-6 border-b border-outline-variant/10 flex justify-between items-center bg-surface-container-low/30 shrink-0">
					<h3 class="text-lg font-bold text-on-surface">Edit Subscription Package</h3>
					<button type="button" data-close-modal="edit-package-modal" class="p-1.5 hover:bg-surface-container-high/50 rounded-full transition-colors"><i class="fa-solid fa-xmark"></i></button>
				</div>
				
				<div class="p-8 space-y-4 overflow-y-auto flex-1 dlm-hover-scrollbar">
					<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
						<div class="space-y-1">
							<label class="text-xs font-bold text-on-surface-variant uppercase">Package Name *</label>
							<input name="package_name" id="edit-package-name" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 text-sm focus:border-primary focus:ring-0" type="text" required>
						</div>
						<div class="space-y-1">
							<label class="text-xs font-bold text-on-surface-variant uppercase">Badge Label (Optional)</label>
							<input name="package_badge" id="edit-package-badge" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 text-sm focus:border-primary focus:ring-0" type="text" placeholder="e.g. The Scholar, BEST VALUE">
						</div>
					</div>

					<div class="space-y-1">
						<label class="text-xs font-bold text-on-surface-variant uppercase">Short Description</label>
						<input name="package_description" id="edit-package-description" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 text-sm focus:border-primary focus:ring-0" type="text">
					</div>

					<div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
						<div class="space-y-1">
							<label class="text-xs font-bold text-on-surface-variant uppercase">Billing Cycle *</label>
							<select name="billing_cycle" id="edit-package-interval" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm">
								<option value="monthly">Monthly</option>
								<option value="yearly">Annual / Yearly</option>
								<option value="lifetime">Lifetime Access</option>
							</select>
						</div>
						<div class="space-y-1">
							<label class="text-xs font-bold text-on-surface-variant uppercase">Price ($) *</label>
							<input name="package_price" id="edit-package-price" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 text-sm focus:border-primary focus:ring-0" type="number" step="0.01" min="0" required>
						</div>
						<div class="space-y-1">
							<label class="text-xs font-bold text-on-surface-variant uppercase">Status *</label>
							<select name="package_status" id="edit-package-status" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-sm">
								<option value="active">Active (Public)</option>
								<option value="inactive">Inactive (Retired)</option>
							</select>
						</div>
					</div>

					<div class="space-y-1">
						<label class="text-xs font-bold text-on-surface-variant uppercase">Plan Benefits / Bullet Features (One per line)</label>
						<textarea name="package_features" id="edit-package-features" class="w-full px-4 py-2.5 rounded-xl border border-outline-variant/30 focus:border-primary focus:ring-0 text-xs font-mono" rows="4"></textarea>
					</div>

					<!-- Gateway Integrations & Mappings -->
					<div class="border-t border-outline-variant/10 pt-4 space-y-4">
						<div>
							<h4 class="text-xs font-bold text-primary uppercase tracking-wider flex items-center gap-1.5">
								<i class="fa-solid fa-wand-magic-sparkles text-primary"></i>
								Automated Gateway Provisioning & Mappings
							</h4>
							<p class="text-[11px] text-secondary mt-0.5">
								Gateway products and plans are created <strong>automatically</strong> in your payment accounts on save. Leave fields empty to auto-provision, or paste existing IDs to map manually.
							</p>
						</div>

						<div class="space-y-3 bg-surface-container-lowest/50 p-4 rounded-2xl border border-outline-variant/20">
							<!-- Stripe Price ID -->
							<div class="space-y-1">
								<div class="flex justify-between items-baseline">
									<label class="text-[11px] font-bold text-on-surface-variant uppercase flex items-center gap-1">
										<i class="fa-brands fa-stripe text-primary text-base"></i> Stripe Price ID
									</label>
									<span class="text-[10px] text-primary font-bold bg-primary/10 px-2 py-0.5 rounded-md">Auto-Sync on Save</span>
								</div>
								<input name="stripe_price_id" id="edit-package-stripe-price" class="w-full px-3.5 py-2 rounded-xl border border-outline-variant/30 text-xs focus:border-primary focus:ring-0 font-mono" type="text" placeholder="Auto-generated (or paste price_...)">
								<p class="text-[10px] text-secondary leading-relaxed">
									Leave blank to automatically create a Product & Price on Stripe via API, or enter an existing <code>price_...</code> ID.
								</p>
							</div>

							<!-- PayPal Plan ID -->
							<div class="space-y-1 pt-2 border-t border-outline-variant/10">
								<div class="flex justify-between items-baseline">
									<label class="text-[11px] font-bold text-on-surface-variant uppercase flex items-center gap-1">
										<i class="fa-brands fa-paypal text-blue-600 text-sm"></i> PayPal Plan ID
									</label>
									<span class="text-[10px] text-blue-700 font-bold bg-blue-50 px-2 py-0.5 rounded-md">Auto-Sync on Save</span>
								</div>
								<input name="paypal_plan_id" id="edit-package-paypal-plan" class="w-full px-3.5 py-2 rounded-xl border border-outline-variant/30 text-xs focus:border-primary focus:ring-0 font-mono" type="text" placeholder="Auto-generated (or paste P-...)">
								<p class="text-[10px] text-secondary leading-relaxed">
									Leave blank to automatically create an active Catalog Product & Subscription Plan on PayPal, or enter an existing <code>P-...</code> ID.
								</p>
							</div>

							<!-- WooCommerce Virtual Product -->
							<?php if ( class_exists( 'WooCommerce' ) ) : ?>
								<div class="space-y-1 pt-2 border-t border-outline-variant/10">
									<div class="flex justify-between items-baseline">
										<label class="text-[11px] font-bold text-on-surface-variant uppercase flex items-center gap-1">
											<i class="fa-solid fa-bag-shopping text-purple-600 text-xs"></i> WooCommerce Product
										</label>
										<span class="text-[10px] text-green-700 font-bold bg-green-50 px-2 py-0.5 rounded-md">Auto-Generated</span>
									</div>
									<select name="wc_product_id" id="edit-package-wc-product" class="w-full px-3.5 py-2 rounded-xl border border-outline-variant/30 text-xs focus:border-primary focus:ring-0">
										<option value="0"><?php esc_html_e( '— Auto-Generate Virtual Product (Recommended) —', 'digital-library-membership' ); ?></option>
										<?php 
										$wc_prods = get_posts( array( 'post_type' => 'product', 'posts_per_page' => -1 ) );
										foreach ( $wc_prods as $wcp ) {
											echo '<option value="' . intval( $wcp->ID ) . '">' . esc_html( $wcp->post_title ) . ' (#' . intval( $wcp->ID ) . ')</option>';
										}
										?>
									</select>
									<p class="text-[10px] text-secondary leading-relaxed">
										Leave as <em>Auto-Generate</em> to automatically create and sync a hidden virtual WooCommerce product upon save.
									</p>
								</div>
							<?php endif; ?>
						</div>
					</div>
				</div>

				<div class="px-8 py-5 border-t border-outline-variant/10 bg-surface-container-low/30 flex justify-end gap-3 shrink-0">
					<button type="button" data-close-modal="edit-package-modal" class="px-5 py-2.5 rounded-xl font-bold text-sm text-secondary hover:bg-secondary-container/30 transition-all">Cancel</button>
					<button type="submit" class="px-5 py-2.5 rounded-xl font-bold text-sm bg-primary text-white hover:opacity-90">Save Changes</button>
				</div>
			</form>
		</div>
	</div>

	<!-- Delete Package Modal -->
	<div id="delete-package-modal" class="fixed inset-0 z-[1000] items-center justify-center p-4 hidden">
		<div class="absolute inset-0 modal-backdrop" data-close-modal="delete-package-modal"></div>
		<div class="relative bg-white w-full max-w-md rounded-3xl shadow-xl overflow-hidden border border-outline-variant/20 animate-in fade-in zoom-in duration-200 z-10">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="dlm_delete_package">
				<input type="hidden" name="package_id" id="delete-package-id" value="">
				<?php wp_nonce_field( 'dlm_package_action_nonce', 'dlm_nonce' ); ?>

				<div class="p-8 text-center space-y-4">
					<div class="w-16 h-16 bg-red-100 text-error-red rounded-full flex items-center justify-center mx-auto text-2xl">
						<i class="fa-solid fa-trash-can"></i>
					</div>
					<h3 class="text-xl font-bold text-on-surface">Delete Subscription Package</h3>
					<p class="text-sm text-secondary leading-relaxed">
						Are you sure you want to delete <strong id="delete-package-name-display" class="text-on-surface"></strong>?
					</p>

					<!-- Informational Active Subscribers Warning -->
					<div id="delete-package-subscribers-warning" class="hidden text-left p-4 bg-amber-50 border border-amber-200 rounded-2xl text-xs text-amber-900 leading-relaxed space-y-1.5">
						<div class="flex items-center gap-2 font-bold text-amber-800">
							<i class="fa-solid fa-triangle-exclamation"></i>
							<span>Active Subscribers Note</span>
						</div>
						<p>
							This package currently has <strong id="delete-package-subscribers-count">0</strong> active member(s). Deleting will remove the package configuration from your plans. You can also choose to <strong>Deactivate</strong> it instead so it is hidden from new signups while leaving existing member records untouched.
						</p>
					</div>
				</div>
				<div class="px-8 py-5 border-t border-outline-variant/10 bg-surface-container-low/30 flex justify-end gap-3">
					<button type="button" data-close-modal="delete-package-modal" class="px-6 py-3 rounded-xl font-bold text-sm text-secondary hover:bg-secondary-container/30 transition-all">Cancel</button>
					<button type="submit" class="px-6 py-3 rounded-xl font-bold text-sm bg-error-red text-white hover:opacity-90 transition-all shadow-md">Delete Package</button>
				</div>
			</form>
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

