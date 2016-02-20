PHPUNIT = vendor/phpunit/phpunit/phpunit
TESTS = tests/

test:
	$(PHPUNIT) --exclude-group integration $(TESTS)

integration-test:
	$(PHPUNIT) --group integration $(TESTS)
