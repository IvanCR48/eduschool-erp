    </main>
    </div>
    <footer class="main-footer">
        <div class="footer-content">
            <p>&copy; <?php echo date("Y"); ?> <?php echo htmlspecialchars(\SistemaAdmin\Bootstrap\AppRequestInit::systemName(), ENT_QUOTES, 'UTF-8'); ?>. <?php echo htmlspecialchars(__('footer.rights'), ENT_QUOTES, 'UTF-8'); ?></p>
            <p><?php echo htmlspecialchars(__('footer.subtitle'), ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
    </footer>
</body>
</html>
