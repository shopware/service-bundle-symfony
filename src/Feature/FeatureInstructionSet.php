<?php

namespace Shopware\ServiceBundle\Feature;

use function Symfony\Component\String\s;

class FeatureInstructionSet
{
    /**
     * @param array<FeatureInstruction> $instructions
     */
    public function __construct(private array $instructions)
    {
        $this->validate($this->instructions);
    }
    
    public static function fromArray(array $instructions): self
    {
        $groupedByName = [];
        foreach ($instructions as $instruction) {
            if (!isset($groupedByName[$instruction['name']])) {
                $groupedByName[$instruction['name']] = [];
                $instruction['op'] = FeatureInstructionType::INSTALL;
            } elseif ($instruction['remove'] === true) {
                $instruction['op'] = FeatureInstructionType::REMOVE;
            } else {
                $instruction['op'] = FeatureInstructionType::UPDATE;
            }

            $groupedByName[$instruction['name']][] = $instruction;
        }

        $instructions = array_merge(...array_values($groupedByName));

        return new self(array_values(array_map(function (array $feature) {
            $featureTypeClass = 'Shopware\ServiceBundle\Feature\Features\\' . $feature['type'];

            return match($feature['op']) {
                FeatureInstructionType::REMOVE => FeatureInstruction::removal($feature['name'], $feature['minimumShopwareVersion']),
                default => new FeatureInstruction(
                    $feature['name'],
                    $feature['op'],
                    $feature['minimumShopwareVersion'],
                    $featureTypeClass::fromArray($feature['config'])
                )
            };
        }, $instructions)));
    }

    /**
     * @param array<FeatureInstruction> $instructions
     */
    public function validate(array $instructions): bool
    {
        foreach ($instructions as $instruction) {
            if ($instruction->type === FeatureInstructionType::REMOVE) {
                continue;
            }

            if (!$instruction->feature->validate($instruction->type)) {
                return false;
            }
        }

        return true;
    }

    public function getDelta(ShopOperation $shop): array
    {
        $instructions = [];
        foreach ($this->instructions as $instruction) {
            if ($instruction->match($shop)) {
                $instructions[] = $instruction;
            }
        }

        //deal with removals
        $instructions = array_reverse($instructions);
        foreach ($instructions as $instruction) {
            if ($instruction->type === FeatureInstructionType::REMOVE) {
                $instructions = array_values(array_filter($instructions, function (FeatureInstruction $i) use ($instruction) {
                    return $i->name !== $instruction->name;
                }));
            }
        }

        $instructions = array_reverse($instructions);

        //group instructions by name
        $groupedInstructions = [];
        foreach ($instructions as $instruction) {
            if (!isset($groupedInstructions[$instruction->name])) {
                $groupedInstructions[$instruction->name] = [];
            }

            $groupedInstructions[$instruction->name][] = $instruction;
        }

        foreach ($groupedInstructions as $name => $instructionGroup) {

            //if more than one install type, throw exception
            $installTypes = array_filter($instructionGroup, fn(FeatureInstruction $i) => $i->type === FeatureInstructionType::INSTALL);

            if (count($installTypes) > 1) {
                throw new \Exception('Cannot have more than one install type for feature ' . $name);
            }

            //sort instructions by minimum version
            usort($instructionGroup, function (FeatureInstruction $a, FeatureInstruction $b) {
                if ($a->type === FeatureInstructionType::INSTALL) {
                    return -1;
                }

                return version_compare($a->minimumShopwareVersion, $b->minimumShopwareVersion);
            });

            $groupedInstructions[$name] = $instructionGroup;
        }

        return $this->toPatch($groupedInstructions);

        return $patch;
    }

    private function toPatch(array $groupedInstructions): array
    {
        $features = [];
        foreach ($groupedInstructions as $name => $instructionGroup) {
            $typeClass = $instructionGroup[0]->feature::class;
            $featureType = $this->classNameToKey($typeClass);

            $features[] = [
                'type' => $featureType,
                'data' => array_merge(...array_map(fn (FeatureInstruction $instruction) => $instruction->feature->getConfig(), $instructionGroup))
            ];
        }

        $payload = [];

        foreach ($features as $feature) {
            if (!isset($payload[$feature['type']])) {
                $payload[$feature['type']] = [];
            }

            $payload[$feature['type']][] = $feature['data'];
        }

        return $payload;
    }

    /**
     * @return class-string
     */
    private function classNameToKey(string $class): string
    {
        $parts = explode('\\', $class);
        $lastPart = end($parts);

        return s($lastPart)->snake()->toString();
    }
}