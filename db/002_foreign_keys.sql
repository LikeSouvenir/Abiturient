-- Ограничения внешнего ключа таблицы `programs`
ALTER TABLE `programs`
  ADD CONSTRAINT `programs_ibfk_1` FOREIGN KEY (`direction_id`) REFERENCES `directions` (`id`) ON DELETE CASCADE;

-- При необходимости добавить другие внешние ключи:
-- ALTER TABLE `bundles`
--   ADD CONSTRAINT `bundles_ibfk_1` FOREIGN KEY (`establishment_id`) REFERENCES `establishments` (`id`) ON DELETE CASCADE,
--   ADD CONSTRAINT `bundles_ibfk_2` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE CASCADE,
--   ADD CONSTRAINT `bundles_ibfk_3` FOREIGN KEY (`cluster_id`) REFERENCES `clusters` (`id`) ON DELETE SET NULL;
